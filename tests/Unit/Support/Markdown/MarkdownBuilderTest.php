<?php

use App\Support\Markdown\MarkdownBuilder;

it('creates a heading', function (): void {
    $result = MarkdownBuilder::make()
        ->heading('Title')
        ->toString();

    expect($result)->toBe("# Title\n");
});

it('creates headings at different levels', function (): void {
    $result = MarkdownBuilder::make()
        ->heading('H1')
        ->heading('H2', 2)
        ->heading('H3', 3)
        ->toString();

    expect($result)->toContain('# H1')
        ->toContain('## H2')
        ->toContain('### H3');
});

it('renders a field with label and value', function (): void {
    $result = MarkdownBuilder::make()
        ->field('Name', 'John')
        ->toString();

    expect($result)->toBe('**Name:** John');
});

it('renders a field with a suffix', function (): void {
    $result = MarkdownBuilder::make()
        ->field('Weight', 80, 'kg')
        ->toString();

    expect($result)->toBe('**Weight:** 80 kg');
});

it('skips field when value is null', function (): void {
    $result = MarkdownBuilder::make()
        ->field('Name', 'John')
        ->field('Age', null)
        ->field('City', 'Amsterdam')
        ->toString();

    expect($result)->toBe("**Name:** John\n**City:** Amsterdam");
});

it('converts boolean field values to Yes/No', function (): void {
    $result = MarkdownBuilder::make()
        ->field('Active', true)
        ->field('Deleted', false)
        ->toString();

    expect($result)->toBe("**Active:** Yes\n**Deleted:** No");
});

it('renders a raw line', function (): void {
    $result = MarkdownBuilder::make()
        ->line('Some text here')
        ->toString();

    expect($result)->toBe('Some text here');
});

it('renders a blank line', function (): void {
    $result = MarkdownBuilder::make()
        ->line('Before')
        ->blankLine()
        ->line('After')
        ->toString();

    expect($result)->toBe("Before\n\nAfter");
});

it('renders a table with header and rows', function (): void {
    $result = MarkdownBuilder::make()
        ->tableHeader(['Name', 'Age'])
        ->tableRow(['John', 30])
        ->tableRow(['Jane', 25])
        ->toString();

    expect($result)->toBe(
        "| Name | Age |\n|---|---|\n| John | 30 |\n| Jane | 25 |"
    );
});

it('renders null table cells as dashes', function (): void {
    $result = MarkdownBuilder::make()
        ->tableHeader(['Name', 'Value'])
        ->tableRow(['Test', null])
        ->toString();

    expect($result)->toContain('| Test | - |');
});

it('renders list items', function (): void {
    $result = MarkdownBuilder::make()
        ->listItem('First')
        ->listItem('Second')
        ->toString();

    expect($result)->toBe("- First\n- Second");
});

it('renders indented list items', function (): void {
    $result = MarkdownBuilder::make()
        ->listItem('Parent')
        ->listItem('Child', 1)
        ->listItem('Grandchild', 2)
        ->toString();

    expect($result)->toBe("- Parent\n  - Child\n    - Grandchild");
});

it('conditionally executes with when()', function (): void {
    $result = MarkdownBuilder::make()
        ->field('Always', 'shown')
        ->when(true, fn (MarkdownBuilder $md) => $md->field('Conditional', 'visible'))
        ->when(false, fn (MarkdownBuilder $md) => $md->field('Hidden', 'never'))
        ->toString();

    expect($result)->toContain('**Conditional:** visible')
        ->not->toContain('Hidden');
});

it('iterates with each()', function (): void {
    $items = ['Alpha', 'Beta', 'Gamma'];

    $result = MarkdownBuilder::make()
        ->each($items, fn (string $item, MarkdownBuilder $md) => $md->listItem($item))
        ->toString();

    expect($result)->toBe("- Alpha\n- Beta\n- Gamma");
});

it('skips each() with empty iterable', function (): void {
    $result = MarkdownBuilder::make()
        ->line('Before')
        ->each([], fn (string $item, MarkdownBuilder $md) => $md->listItem($item))
        ->line('After')
        ->toString();

    expect($result)->toBe("Before\nAfter");
});

it('is castable to string via Stringable', function (): void {
    $md = MarkdownBuilder::make()->heading('Test');

    expect((string) $md)->toBe("# Test\n");
});

it('builds a complete document', function (): void {
    $result = MarkdownBuilder::make()
        ->heading('Profile')
        ->field('Name', 'John')
        ->field('Age', 30)
        ->field('Active', true)
        ->blankLine()
        ->heading('Stats', 2)
        ->tableHeader(['Metric', 'Value'])
        ->tableRow(['Score', 95])
        ->tableRow(['Rank', 1])
        ->toString();

    expect($result)
        ->toContain('# Profile')
        ->toContain('**Name:** John')
        ->toContain('**Active:** Yes')
        ->toContain('## Stats')
        ->toContain('| Score | 95 |');
});
