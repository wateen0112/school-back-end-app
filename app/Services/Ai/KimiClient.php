<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AiClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KimiClient implements AiClient
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;
    protected ?string $proxy;
    protected float $temperature;

    public function __construct()
    {
        $this->apiKey = trim((string) config('services.kimi.key', ''));
        $this->model = config('services.kimi.model', 'kimi-k2.6');
        $this->baseUrl = rtrim((string) config('services.kimi.base_url', 'https://api.moonshot.ai/v1'), '/');
        $this->proxy = config('services.kimi.proxy');
        $this->temperature = (float) config('services.kimi.temperature', 1.0);
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    protected function maskKey(string $key): string
    {
        if ($key === '') {
            return '(empty)';
        }

        $length = strlen($key);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($key, 0, 4) . str_repeat('*', $length - 8) . substr($key, -4);
    }

    protected function http(): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::timeout(120)->retry(2, 500)
            ->withToken($this->apiKey, 'Bearer')
            ->withHeaders([
                'Content-Type' => 'application/json',
            ]);

        if ($this->proxy) {
            $client = $client->withOptions(['proxy' => $this->proxy]);
        }

        return $client;
    }

    public function chat(array $messages, array $toolDefinitions, callable $toolExecutor): array
    {
        if (! $this->isConfigured()) {
            throw new AiNotConfiguredException();
        }

        $maxIterations = 8;
        $toolCalls = [];

        for ($i = 0; $i < $maxIterations; $i++) {
            $payload = $this->buildPayload($messages, $toolDefinitions);

            Log::debug('Kimi API request payload', [
                'url' => "{$this->baseUrl}/chat/completions",
                'model' => $this->model,
                'key_preview' => $this->maskKey($this->apiKey),
                'payload' => $payload,
                'json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);

            $response = $this->http()->post("{$this->baseUrl}/chat/completions", $payload);

            if (! $response->successful()) {
                Log::error('Kimi API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'key_preview' => $this->maskKey($this->apiKey),
                    'url' => "{$this->baseUrl}/chat/completions",
                    'model' => $this->model,
                    'payload' => $payload,
                ]);
                $errorMessage = $response->json('error.message') ?? 'Kimi API returned an error';
                throw new AiServiceException($errorMessage);
            }

            $data = $response->json();
            $choice = $data['choices'][0] ?? null;

            if ($choice === null) {
                return ['text' => 'عذراً، لم أتمكن من معالجة طلبك.', 'toolCalls' => $toolCalls];
            }

            $message = $choice['message'] ?? [];
            $toolCallsFromResponse = $message['tool_calls'] ?? [];
            $content = $message['content'] ?? '';
            $finishReason = $choice['finish_reason'] ?? 'stop';

            if (empty($toolCallsFromResponse)) {
                return ['text' => is_string($content) ? $content : 'تمت المعالجة.', 'toolCalls' => $toolCalls];
            }

            // Add the assistant message with tool calls to the conversation
            $messages[] = ['role' => 'model', 'parts' => $this->convertAssistantToolCallsToGeminiParts($toolCallsFromResponse)];

            $functionResponseParts = [];

            foreach ($toolCallsFromResponse as $call) {
                if (($call['type'] ?? '') !== 'function') {
                    continue;
                }

                $fnName = $call['function']['name'] ?? '';
                $fnArgs = [];
                $arguments = $call['function']['arguments'] ?? '{}';

                if (is_string($arguments) && $arguments !== '') {
                    $decoded = json_decode($arguments, true);
                    $fnArgs = is_array($decoded) ? $decoded : [];
                } elseif (is_array($arguments)) {
                    $fnArgs = $arguments;
                }

                $result = $this->sanitizeResponse($toolExecutor($fnName, $fnArgs));
                $toolCalls[] = ['name' => $fnName, 'args' => $fnArgs, 'result' => $result];

                $functionResponseParts[] = [
                    'functionResponse' => [
                        'name' => $fnName,
                        'response' => $result ?? (object) [],
                        'tool_call_id' => $call['id'] ?? '',
                    ],
                ];
            }

            $messages[] = ['role' => 'user', 'parts' => $functionResponseParts];
            $messages = $this->sanitizeMessages($messages);
        }

        return ['text' => 'تمت المعالجة (بعد عدة استعلامات).', 'toolCalls' => $toolCalls];
    }

    protected function buildPayload(array $messages, array $toolDefinitions): array
    {
        $payload = [
            'model' => $this->model,
            'messages' => $this->convertMessagesToOpenAi($messages),
            'temperature' => $this->temperature,
            'max_tokens' => 4096,
        ];

        if (! empty($toolDefinitions)) {
            $payload['tools'] = array_map(fn (array $def) => [
                'type' => 'function',
                'function' => [
                    'name' => $def['name'],
                    'description' => $def['description'],
                    'parameters' => $this->normalizeParameters($def['parameters'] ?? []),
                ],
            ], $toolDefinitions);

            $payload['tool_choice'] = 'auto';
        }

        return $payload;
    }

    protected function normalizeParameters(mixed $parameters): mixed
    {
        if (is_array($parameters)) {
            $normalized = [];
            foreach ($parameters as $key => $value) {
                if ($key === 'type' && is_string($value)) {
                    $normalized[$key] = strtolower($value);
                } else {
                    $normalized[$key] = $this->normalizeParameters($value);
                }
            }
            return $normalized;
        }

        if (is_object($parameters)) {
            return json_decode(json_encode($parameters), true);
        }

        return $parameters;
    }

    protected function convertMessagesToOpenAi(array $messages): array
    {
        $openAiMessages = [];

        foreach ($messages as $message) {
            $role = $message['role'] === 'model' ? 'assistant' : 'user';
            $parts = $message['parts'] ?? [];

            if ($parts === []) {
                continue;
            }

            $textParts = [];
            $toolCalls = [];
            $toolResponses = [];

            foreach ($parts as $part) {
                if (isset($part['text'])) {
                    $textParts[] = is_string($part['text']) ? $part['text'] : '';
                } elseif (isset($part['functionCall'])) {
                    $toolCalls[] = [
                        'id' => $part['functionCall']['tool_call_id'] ?? $this->generateToolCallId(),
                        'type' => 'function',
                        'function' => [
                            'name' => $part['functionCall']['name'] ?? '',
                            'arguments' => json_encode($part['functionCall']['args'] ?? (object) [], JSON_UNESCAPED_UNICODE),
                        ],
                    ];
                } elseif (isset($part['functionResponse'])) {
                    $toolResponses[] = [
                        'role' => 'tool',
                        'tool_call_id' => $part['functionResponse']['tool_call_id'] ?? $this->generateToolCallId(),
                        'name' => $part['functionResponse']['name'] ?? '',
                        'content' => json_encode($this->sanitizeResponse($part['functionResponse']['response'] ?? (object) []), JSON_UNESCAPED_UNICODE),
                    ];
                }
            }

            if ($role === 'assistant' && ($textParts !== [] || $toolCalls !== [])) {
                $assistantMessage = [
                    'role' => 'assistant',
                    'content' => $textParts !== [] ? implode("\n\n", $textParts) : null,
                ];

                if ($toolCalls !== []) {
                    $assistantMessage['tool_calls'] = $toolCalls;
                }

                $openAiMessages[] = $assistantMessage;
            } elseif ($textParts !== []) {
                $openAiMessages[] = [
                    'role' => $role,
                    'content' => implode("\n\n", $textParts),
                ];
            }

            foreach ($toolResponses as $toolResponse) {
                $openAiMessages[] = $toolResponse;
            }
        }

        return $openAiMessages;
    }

    protected function convertAssistantToolCallsToGeminiParts(array $toolCalls): array
    {
        $parts = [];

        foreach ($toolCalls as $call) {
            if (($call['type'] ?? '') !== 'function') {
                continue;
            }

            $arguments = $call['function']['arguments'] ?? '{}';
            $args = [];
            if (is_string($arguments) && $arguments !== '') {
                $decoded = json_decode($arguments, true);
                $args = is_array($decoded) ? $decoded : [];
            } elseif (is_array($arguments)) {
                $args = $arguments;
            }

            $parts[] = [
                'functionCall' => [
                    'name' => $call['function']['name'] ?? '',
                    'args' => $args,
                    'tool_call_id' => $call['id'] ?? '',
                ],
            ];
        }

        return $parts;
    }

    protected function generateToolCallId(): string
    {
        return 'call_' . bin2hex(random_bytes(12));
    }

    protected function sanitizeMessages(array $messages): array
    {
        $sanitized = [];

        foreach ($messages as $message) {
            if (! isset($message['role'], $message['parts']) || ! is_array($message['parts'])) {
                continue;
            }

            $parts = [];
            foreach ($message['parts'] as $part) {
                if (! is_array($part)) {
                    continue;
                }

                if (isset($part['text'])) {
                    $text = is_string($part['text']) ? trim($part['text']) : '';
                    if ($text === '') {
                        continue;
                    }
                    $parts[] = ['text' => $text];
                } elseif (isset($part['functionCall']) && is_array($part['functionCall'])) {
                    $rawArgs = $part['functionCall']['args'] ?? [];
                    $args = is_array($rawArgs) && $rawArgs === [] ? (object) [] : $rawArgs;

                    $functionCallPart = [
                        'functionCall' => [
                            'name' => $part['functionCall']['name'] ?? '',
                            'args' => $args,
                        ],
                    ];

                    if (isset($part['functionCall']['tool_call_id'])) {
                        $functionCallPart['functionCall']['tool_call_id'] = $part['functionCall']['tool_call_id'];
                    }

                    $parts[] = $functionCallPart;
                } elseif (isset($part['functionResponse']) && is_array($part['functionResponse'])) {
                    $functionResponsePart = [
                        'functionResponse' => [
                            'name' => $part['functionResponse']['name'] ?? '',
                            'response' => $this->sanitizeResponse($part['functionResponse']['response'] ?? (object) []),
                        ],
                    ];

                    if (isset($part['functionResponse']['tool_call_id'])) {
                        $functionResponsePart['functionResponse']['tool_call_id'] = $part['functionResponse']['tool_call_id'];
                    }

                    $parts[] = $functionResponsePart;
                }
            }

            if ($parts === []) {
                continue;
            }

            $sanitized[] = ['role' => $message['role'], 'parts' => $parts];
        }

        return $sanitized;
    }

    protected function sanitizeResponse(mixed $response): mixed
    {
        if (is_array($response)) {
            $clean = [];
            foreach ($response as $key => $value) {
                $clean[$key] = $this->sanitizeResponse($value);
            }
            return $clean;
        }

        if (is_object($response)) {
            return json_decode(json_encode($response), true) ?? (object) [];
        }

        if (is_string($response)) {
            return trim($response) === '' ? '' : $response;
        }

        return $response;
    }

    public function model(): string
    {
        return $this->model;
    }
}
