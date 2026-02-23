<?php

declare(strict_types=1);

namespace App\Tools;

readonly class ToolResult
{
    /**
     * @param  array<string, mixed>  $data
     */
    private function __construct(
        private array $data,
        private ?string $error,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function success(array $data): self
    {
        return new self(data: ['success' => true, ...$data], error: null);
    }

    public static function error(string $message): self
    {
        return new self(data: ['error' => $message], error: $message);
    }

    public function failed(): bool
    {
        return $this->error !== null;
    }

    public function errorMessage(): ?string
    {
        return $this->error;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
