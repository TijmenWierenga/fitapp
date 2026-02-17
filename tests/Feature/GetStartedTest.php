<?php

use App\Livewire\GetStarted;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\get;

it('can access get started page as guest', function () {
    get(route('get-started'))
        ->assertOk()
        ->assertSeeLivewire(GetStarted::class)
        ->assertSee('Connect Your AI to');
});

it('can access get started page as authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    get(route('get-started'))
        ->assertOk()
        ->assertSeeLivewire(GetStarted::class);
});

it('shows setup method selection on step 1', function () {
    Livewire::test(GetStarted::class)
        ->assertSee('Choose Your Setup Method')
        ->assertSee('Claude Desktop')
        ->assertSee('Claude Code CLI')
        ->assertSee('ChatGPT Desktop')
        ->assertSee('VS Code / Copilot')
        ->assertSee('Other MCP Client');
});

it('can navigate between steps', function () {
    Livewire::test(GetStarted::class)
        ->assertSet('currentStep', 1)
        ->call('goToStep', 2)
        ->assertSet('currentStep', 2)
        ->call('goToStep', 3)
        ->assertSet('currentStep', 3);
});

it('can select desktop setup method', function () {
    Livewire::test(GetStarted::class)
        ->assertSet('setupMethod', 'desktop')
        ->call('selectMethod', 'cli')
        ->assertSet('setupMethod', 'cli')
        ->assertSet('currentStep', 2);
});

it('can select cli setup method', function () {
    Livewire::test(GetStarted::class)
        ->call('selectMethod', 'cli')
        ->assertSet('setupMethod', 'cli')
        ->assertSet('currentStep', 2);
});

it('can select chatgpt setup method', function () {
    Livewire::test(GetStarted::class)
        ->call('selectMethod', 'chatgpt')
        ->assertSet('setupMethod', 'chatgpt')
        ->assertSet('currentStep', 2);
});

it('can select vscode setup method', function () {
    Livewire::test(GetStarted::class)
        ->call('selectMethod', 'vscode')
        ->assertSet('setupMethod', 'vscode')
        ->assertSet('currentStep', 2);
});

it('can select other setup method', function () {
    Livewire::test(GetStarted::class)
        ->call('selectMethod', 'other')
        ->assertSet('setupMethod', 'other')
        ->assertSet('currentStep', 2);
});

it('rejects invalid setup method', function () {
    Livewire::test(GetStarted::class)
        ->call('selectMethod', 'invalid')
        ->assertSet('setupMethod', 'desktop')
        ->assertSet('currentStep', 1);
});

it('shows desktop instructions when desktop selected', function () {
    Livewire::test(GetStarted::class)
        ->call('selectMethod', 'desktop')
        ->assertSee('Open Claude Desktop Settings')
        ->assertSee('Navigate to Connectors')
        ->assertSee('Add a Custom Connector')
        ->assertSee('MCP Server URL');
});

it('shows cli instructions when cli selected', function () {
    Livewire::test(GetStarted::class)
        ->call('selectMethod', 'cli')
        ->assertSee('Run this command in your terminal')
        ->assertSee('claude mcp add');
});

it('shows chatgpt instructions when chatgpt selected', function () {
    Livewire::test(GetStarted::class)
        ->call('selectMethod', 'chatgpt')
        ->assertSee('Open ChatGPT Desktop Settings')
        ->assertSee('Navigate to Extensions')
        ->assertSee('Add Custom Connector');
});

it('shows vscode instructions when vscode selected', function () {
    Livewire::test(GetStarted::class)
        ->call('selectMethod', 'vscode')
        ->assertSee('Open VS Code Settings')
        ->assertSee('Open settings.json');
});

it('shows other client instructions when other selected', function () {
    Livewire::test(GetStarted::class)
        ->call('selectMethod', 'other')
        ->assertSee('any MCP-compatible client')
        ->assertSee('MCP Server URL');
});

it('shows progressive prompts on step 3', function () {
    Livewire::test(GetStarted::class)
        ->call('goToStep', 3)
        ->assertSee('Start Your AI Intake')
        ->assertSee('Beginner')
        ->assertSee('Intermediate')
        ->assertSee('Advanced');
});

it('generates correct MCP endpoint URL', function () {
    $component = new GetStarted;

    expect($component->getMcpEndpoint())->toContain('/mcp/workout');
});

it('generates CLI command without auth header', function () {
    $component = new GetStarted;
    $command = $component->getCliCommand();

    expect($command)
        ->toContain('claude mcp add')
        ->toContain('--transport http')
        ->toContain('Traiq')
        ->not->toContain('Authorization')
        ->not->toContain('Bearer');
});

it('returns correct method label', function () {
    $component = new GetStarted;

    expect($component->getMethodLabel())->toBe('Claude Desktop');

    $component->setupMethod = 'cli';
    expect($component->getMethodLabel())->toBe('Claude Code CLI');

    $component->setupMethod = 'chatgpt';
    expect($component->getMethodLabel())->toBe('ChatGPT Desktop');

    $component->setupMethod = 'vscode';
    expect($component->getMethodLabel())->toBe('VS Code / Copilot');

    $component->setupMethod = 'other';
    expect($component->getMethodLabel())->toBe('Other MCP Client');
});

it('shows dashboard link for authenticated users on step 3', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(GetStarted::class)
        ->call('goToStep', 3)
        ->assertSee('Go to Dashboard');
});

it('does not show dashboard link for guests on step 3', function () {
    Livewire::test(GetStarted::class)
        ->call('goToStep', 3)
        ->assertDontSee('Go to Dashboard');
});
