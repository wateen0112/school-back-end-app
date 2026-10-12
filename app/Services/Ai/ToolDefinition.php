<?php

namespace App\Services\Ai;

class ToolDefinition
{
    public readonly mixed $handler;

    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly array $parameters,
        mixed $handler,
        public readonly array $allowedRoles = ['admin'],
    ) {
        if (! is_callable($handler)) {
            throw new \InvalidArgumentException("Tool handler for '{$name}' must be callable");
        }
        $this->handler = $handler;
    }

    public function toGeminiDefinition(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'parameters' => $this->parameters,
        ];
    }
}