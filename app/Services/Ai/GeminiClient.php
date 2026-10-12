<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AiClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiClient implements AiClient
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;
    protected ?string $proxy;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', '');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
        $this->baseUrl = rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com'), '/');
        $this->proxy = config('services.gemini.proxy');
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    protected function http(): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::timeout(60)->retry(2, 500);
        if ($this->proxy) {
            $client = $client->withOptions(['proxy' => $this->proxy]);
        }
        return $client;
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

                    if (isset($part['thoughtSignature'])) {
                        $functionCallPart['thoughtSignature'] = $part['thoughtSignature'];
                    }
                    if (isset($part['thought_signature'])) {
                        $functionCallPart['thought_signature'] = $part['thought_signature'];
                    }

                    $parts[] = $functionCallPart;
                } elseif (isset($part['functionResponse']) && is_array($part['functionResponse'])) {
                    $parts[] = [
                        'functionResponse' => [
                            'name' => $part['functionResponse']['name'] ?? '',
                            'response' => $this->sanitizeResponse($part['functionResponse']['response'] ?? (object) []),
                        ],
                    ];
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

    public function chat(array $messages, array $toolDefinitions, callable $toolExecutor): array
    {
        if (! $this->isConfigured()) {
            throw new AiNotConfiguredException();
        }

        $messages = $this->sanitizeMessages($messages);
        $maxIterations = 8;
        $toolCalls = [];

        for ($i = 0; $i < $maxIterations; $i++) {
            $payload = array_filter([
                'contents' => $messages,
                'generationConfig' => [
                    'temperature' => 0.7,
                    'maxOutputTokens' => 1024,
                ],
                'tools' => ! empty($toolDefinitions) ? [['functionDeclarations' => $toolDefinitions]] : null,
                'toolConfig' => ! empty($toolDefinitions) ? [
                    'functionCallingConfig' => ['mode' => 'AUTO'],
                ] : null,
            ], fn ($value) => $value !== null);

            Log::debug('Gemini API request payload', [
                'url' => "{$this->baseUrl}/v1beta/models/{$this->model}:generateContent",
                'payload' => $payload,
                'json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);

            $response = $this->http()
                ->withQueryParameters(['key' => $this->apiKey])
                ->post(
                    "{$this->baseUrl}/v1beta/models/{$this->model}:generateContent",
                    $payload
                );

            if (! $response->successful()) {
                Log::error('Gemini API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'payload' => $payload,
                ]);
                $errorMessage = $response->json('error.message') ?? 'Gemini API returned an error';
                if (str_contains($errorMessage, 'location is not supported') || $response->json('error.status') === 'FAILED_PRECONDITION') {
                    throw new AiServiceException('User location is not supported for the API use. Configure an HTTP proxy via GEMINI_PROXY to a supported region.');
                }
                if (str_contains($errorMessage, 'thought_signature')) {
                    throw new AiServiceException('The selected Gemini model requires a thought signature for function calls, but none was provided. Try switching to a non-thinking model such as gemini-1.5-flash or gemini-1.5-pro by setting GEMINI_MODEL.');
                }
                throw new AiServiceException($errorMessage);
            }

            $data = $response->json();
            $candidates = $data['candidates'] ?? [];
            if (empty($candidates)) {
                return ['text' => 'عذراً، لم أتمكن من معالجة طلبك.', 'toolCalls' => $toolCalls];
            }

            $candidate = $candidates[0];

            Log::debug('Gemini API response candidate', ['candidate' => $candidate]);
            $content = $candidate['content'] ?? [];
            $parts = $content['parts'] ?? [];
            $finishReason = $candidate['finishReason'] ?? 'STOP';
            $candidateThoughtSignature = $candidate['thoughtSignature']
                ?? $candidate['thought_signature']
                ?? $content['thoughtSignature']
                ?? $content['thought_signature']
                ?? null;

            $hasFunctionCalls = false;
            $modelFunctionCallParts = [];
            $functionResponseParts = [];

            foreach ($parts as $part) {
                if (isset($part['functionCall'])) {
                    $hasFunctionCalls = true;
                    $fnName = $part['functionCall']['name'];
                    $fnArgs = $part['functionCall']['args'] ?? [];

                    $result = $this->sanitizeResponse($toolExecutor($fnName, $fnArgs));
                    $toolCalls[] = ['name' => $fnName, 'args' => $fnArgs, 'result' => $result];

                    $functionResponseParts[] = [
                        'functionResponse' => [
                            'name' => $fnName,
                            'response' => $result ?? (object) [],
                        ],
                    ];

                    $modelPart = $part;
                    if ($candidateThoughtSignature !== null && ! isset($modelPart['thoughtSignature']) && ! isset($modelPart['thought_signature'])) {
                        $modelPart['thoughtSignature'] = $candidateThoughtSignature;
                    }

                    $modelFunctionCallParts[] = $modelPart;
                }
            }

            if ($hasFunctionCalls) {
                $messages[] = ['role' => 'model', 'parts' => $modelFunctionCallParts];
                $messages[] = ['role' => 'user', 'parts' => $functionResponseParts];
                $messages = $this->sanitizeMessages($messages);
            }

            if (! $hasFunctionCalls) {
                $text = '';
                foreach ($parts as $part) {
                    if (isset($part['text'])) {
                        $text .= $part['text'];
                    }
                }
                return ['text' => $text ?: 'تمت المعالجة.', 'toolCalls' => $toolCalls];
            }
        }

        return ['text' => 'تمت المعالجة (بعد عدة استعلامات).', 'toolCalls' => $toolCalls];
    }

    public function model(): string
    {
        return $this->model;
    }
}