<?php

namespace App\Support\Markdown;

use Stringable;

class MarkdownBuilder implements Stringable
{
    /** @var list<string> */
    private array $lines = [];

    public static function make(): self
    {
        return new self;
    }

    public function heading(string $text, int $level = 1): self
    {
        $prefix = str_repeat('#', $level);
        $this->lines[] = "{$prefix} {$text}";
        $this->lines[] = '';

        return $this;
    }

    public function field(string $label, mixed $value, ?string $suffix = null): self
    {
        if ($value === null) {
            return $this;
        }

        if (is_bool($value)) {
            $value = $value ? 'Yes' : 'No';
        }

        $display = $suffix !== null ? "{$value} {$suffix}" : (string) $value;

        $this->lines[] = "**{$label}:** {$display}";

        return $this;
    }

    public function line(string $text): self
    {
        $this->lines[] = $text;

        return $this;
    }

    public function blankLine(): self
    {
        $this->lines[] = '';

        return $this;
    }

    /**
     * @param  list<string>  $headers
     */
    public function tableHeader(array $headers): self
    {
        $this->lines[] = '| '.implode(' | ', $headers).' |';
        $this->lines[] = '|'.str_repeat('---|', count($headers));

        return $this;
    }

    /**
     * @param  list<string|int|float|null>  $cells
     */
    public function tableRow(array $cells): self
    {
        $formatted = array_map(fn (mixed $cell): string => (string) ($cell ?? '-'), $cells);
        $this->lines[] = '| '.implode(' | ', $formatted).' |';

        return $this;
    }

    public function listItem(string $text, int $indent = 0): self
    {
        $prefix = str_repeat('  ', $indent);
        $this->lines[] = "{$prefix}- {$text}";

        return $this;
    }

    public function when(bool $condition, callable $callback): self
    {
        if ($condition) {
            $callback($this);
        }

        return $this;
    }

    /**
     * @template T
     *
     * @param  iterable<T>  $items
     * @param  callable(T, self): void  $callback
     */
    public function each(iterable $items, callable $callback): self
    {
        foreach ($items as $item) {
            $callback($item, $this);
        }

        return $this;
    }

    public function toString(): string
    {
        return implode("\n", $this->lines);
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
