<?php

namespace App\Services\Ai;

use App\Services\Ai\Contracts\AiClient;
use App\Services\AuditLogger;
use App\Services\Scoping\SchoolContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AssistantService
{
    protected AiClient $aiClient;
    protected SchemaService $schema;
    protected QueryExecutorService $queryExecutor;
    protected ?int $schoolId;
    protected string $role;
    protected ?Model $actor;

    public function __construct(AiClient $aiClient, SchemaService $schema, QueryExecutorService $queryExecutor)
    {
        $this->aiClient = $aiClient;
        $this->schema = $schema;
        $this->queryExecutor = $queryExecutor;
        $this->actor = Auth::guard('api')->user() ?? Auth::guard('web')->user();
        $this->schoolId = SchoolContext::effectiveSchoolId($this->actor);
        $this->role = $this->resolveRole($this->actor);
    }

    public function isConfigured(): bool
    {
        return $this->aiClient->isConfigured();
    }

    public function send(string $message, array $history = []): array
    {
        $systemPrompt = $this->buildSystemPrompt();
        $tools = $this->buildTools();
        $toolDefinitions = array_map(fn(ToolDefinition $t) => $t->toGeminiDefinition(), $tools);
        $toolExecutor = $this->buildToolExecutor($tools);

        $messages = $this->buildMessages($systemPrompt, $message, $history);

        $result = $this->aiClient->chat($messages, $toolDefinitions, $toolExecutor);

        $text = $result['text'];

        AuditLogger::log('ai_assistant_query', 'App\\Models\\User', $this->actor?->id,
            substr($message, 0, 200),
            ['model' => $this->aiClient->model(), 'tools_used' => array_column($result['toolCalls'], 'name'), 'school_id' => $this->schoolId]
        );

        return [
            'reply' => strip_tags($text),
            'html' => $text,
            'tool_calls' => $result['toolCalls'],
            'model' => $this->aiClient->model(),
            'school_id' => $this->schoolId,
            'role' => $this->role,
        ];
    }

    public function suggestedQuestions(): array
    {
        $base = [
            'كم عدد الطلاب المسجلين؟',
            'ما هي نسبة الغياب هذا الشهر؟',
            'اعرض آخر 5 فواتير دراسية.',
        ];

        if ($this->role === 'admin') {
            return array_merge($base, [
                'ما هي الفصول الدراسية المتاحة؟',
                'كم عدد المعلمين في المدرسة؟',
                'اعرض الاشتراكات النشطة.',
            ]);
        }

        return $base;
    }

    protected function resolveRole(?Model $actor): string
    {
        if (! $actor) {
            return 'admin';
        }

        return match (get_class($actor)) {
            \App\User::class => 'admin',
            \App\Models\Teacher::class => 'teacher',
            default => 'admin',
        };
    }

    protected function buildSystemPrompt(): string
    {
        $schemaDescription = $this->schema->describeAllowedTables();
        $schoolContext = $this->schoolId === null
            ? 'المستخدم له صلاحية على جميع المدارس (مدير عام).'
            : "المستخدم تابع للمدرسة رقم: {$this->schoolId}.";

        return <<<PROMPT
أنت مساعد ذكي لنظام إدارة المدارس باللغة العربية.

قواعد العمل:
1. {$schoolContext}
2. عندما يطلب المستخدم بيانات، استخدم أداة run_query لاستعلام قاعدة البيانات.
3. النظام سيقوم تلقائياً بتصفية النتائج حسب المدرسة التابعة للمستخدم.
4. بعد استلام البيانات، قم بتنسيق الإجابة النهائية كـ HTML فقط.
5. لا تستخدم وسوم <script> أو <style> أو أحداث JavaScript.
6. استخدم فقط وسوم HTML الآمنة: <div>, <table>, <thead>, <tbody>, <tr>, <th>, <td>, <ul>, <ol>, <li>, <p>, <strong>, <em>, <span>, <br>, <h1>..<h4>.
7. اجعل الجداول جميلة وبسيطة مع عناوين واضحة.
8. إذا لم تتمكن من الإجابة، قل "عذراً، لا أستطيع الإجابة على هذا الطلب حالياً".
9. لا تختلق بيانات؛ استخدم فقط ما ترجعه الأداة.
10. حافظ على الإجابة مختصرة ومفيدة.

الجداول المتاحة للاستعلام (مع أسماء الأعمدة):
{$schemaDescription}

مثال على استخدام الأداة:
{
  "table": "students",
  "columns": ["name", "email", "Classroom_id"],
  "filters": [{"column": "status", "operator": "=", "value": "active"}],
  "order_by": [{"column": "name", "direction": "asc"}],
  "limit": 10
}
PROMPT;
    }

    protected function buildMessages(string $systemPrompt, string $message, array $history): array
    {
        $messages = [];

        foreach (array_slice($history, -10) as $turn) {
            $role = ($turn['role'] === 'assistant') ? 'model' : 'user';
            $content = trim($turn['content'] ?? '');
            if ($content === '') {
                continue;
            }

            if (! empty($messages)) {
                $lastIndex = count($messages) - 1;
                if ($messages[$lastIndex]['role'] === $role) {
                    $messages[$lastIndex]['parts'][0]['text'] .= "\n\n" . $content;
                    continue;
                }
            }

            $messages[] = ['role' => $role, 'parts' => [['text' => $content]]];
        }

        $currentText = $systemPrompt . "\n\nالطلب: " . $message;

        if (! empty($messages)) {
            $lastIndex = count($messages) - 1;
            if ($messages[$lastIndex]['role'] === 'user') {
                $messages[$lastIndex]['parts'][0]['text'] .= "\n\n" . $currentText;
            } else {
                $messages[] = ['role' => 'user', 'parts' => [['text' => $currentText]]];
            }
        } else {
            $messages[] = ['role' => 'user', 'parts' => [['text' => $currentText]]];
        }

        return $messages;
    }

    protected function buildTools(): array
    {
        return [
            new ToolDefinition(
                name: 'run_query',
                description: 'ينفذ استعلاماً آمناً على قاعدة البيانات ويعيد الصفوف المطابقة. يتم تصفية النتائج تلقائياً حسب مدرسة المستخدم.',
                parameters: [
                    'type' => 'OBJECT',
                    'properties' => [
                        'table' => [
                            'type' => 'STRING',
                            'description' => 'اسم الجدول المراد الاستعلام منه.',
                            'enum' => $this->schema->allowedTables(),
                        ],
                        'columns' => [
                            'type' => 'ARRAY',
                            'description' => 'قائمة بأسماء الأعمدة المراد جلبها. اتركه فارغاً أو استخدم ["*"] لجلب الكل.',
                            'items' => ['type' => 'STRING'],
                        ],
                        'filters' => [
                            'type' => 'ARRAY',
                            'description' => 'شروط التصفية. كل عنصر يحتوي على column و operator و value.',
                            'items' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'column' => ['type' => 'STRING'],
                                    'operator' => [
                                        'type' => 'STRING',
                                        'enum' => ['=', '!=', '<>', '<', '>', '<=', '>=', 'like', 'not like', 'in', 'not in', 'between', 'is null', 'is not null'],
                                    ],
                                    'value' => ['description' => 'القيمة المراد المقارنة بها.'],
                                ],
                                'required' => ['column', 'operator'],
                            ],
                        ],
                        'aggregates' => [
                            'type' => 'ARRAY',
                            'description' => 'دوال التجميع. كل عنصر يحتوي على function و column و alias اختياري.',
                            'items' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'function' => ['type' => 'STRING', 'enum' => ['count', 'sum', 'avg', 'min', 'max']],
                                    'column' => ['type' => 'STRING'],
                                    'alias' => ['type' => 'STRING'],
                                ],
                                'required' => ['function', 'column'],
                            ],
                        ],
                        'group_by' => [
                            'type' => 'ARRAY',
                            'description' => 'أعمدة التجميع.',
                            'items' => ['type' => 'STRING'],
                        ],
                        'order_by' => [
                            'type' => 'ARRAY',
                            'description' => 'ترتيب النتائج.',
                            'items' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'column' => ['type' => 'STRING'],
                                    'direction' => ['type' => 'STRING', 'enum' => ['asc', 'desc']],
                                ],
                                'required' => ['column'],
                            ],
                        ],
                        'limit' => [
                            'type' => 'INTEGER',
                            'description' => 'الحد الأقصى لعدد الصفوف (القيمة الافتراضية 100، الحد الأقصى 500).',
                        ],
                    ],
                    'required' => ['table'],
                ],
                handler: function (array $args) {
                    return $this->queryExecutor->execute($args, $this->schoolId);
                },
                allowedRoles: ['admin', 'teacher'],
            ),
        ];
    }

    protected function buildToolExecutor(array $tools): callable
    {
        $map = [];
        foreach ($tools as $tool) {
            $map[$tool->name] = $tool;
        }

        return function (string $name, array $args) use ($map) {
            if (! isset($map[$name])) {
                return ['error' => "أداة غير معروفة: {$name}"];
            }
            $tool = $map[$name];
            $fn = $tool->handler;
            return $fn($args);
        };
    }
}
