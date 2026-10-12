<?php

namespace App\Services\Ai\Contracts;

interface AiClient
{
    public function isConfigured(): bool;

    /**
     * @param array<int, array{role: string, parts: array<int, array<string, mixed>>>} $messages
     * @param array<int, array{name: string, description: string, parameters: array<string, mixed>}> $toolDefinitions
     * @param callable(string, array): mixed $toolExecutor
     * @return array{text: string, toolCalls: array<int, array<string, mixed>>}
     */
    public function chat(array $messages, array $toolDefinitions, callable $toolExecutor): array;

    public function model(): string;
}
