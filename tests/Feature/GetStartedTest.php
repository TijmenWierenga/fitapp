<?php

use App\Livewire\GetStarted;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\get;

it('can access get started page as guest', function () {
    get(route('get-started'))
        ->assertOk()
        ->assertSeeLivewire(GetStarted::class)
        ->assertSee('Get Started with');
});

it('can access get started page as authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    get(route('get-started'))
        ->assertOk()
        ->assertSeeLivewire(GetStarted::class);
});

it('shows create account CTA for guests on step 1', function () {
    Livewire::test(GetStarted::class)
        ->assertSee('Create an account first')
        ->assertSee('Create Account');
});

it('shows token creation form for authenticated users', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(GetStarted::class)
        ->assertSee('API Key Name')
        ->assertSee('Create API Key');
});

it('can create an API token', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(GetStarted::class)
        ->set('tokenName', 'Test Token')
        ->call('createToken')
        ->assertSet('tokenCreated', true)
        ->assertNotSet('newToken', null);

    expect($user->tokens()->where('name', 'Test Token')->exists())->toBeTrue();
});

it('validates token name is required', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(GetStarted::class)
        ->set('tokenName', '')
        ->call('createToken')
        ->assertHasErrors(['tokenName' => 'required']);
});

it('can navigate between steps', function () {
    Livewire::test(GetStarted::class)
        ->assertSet('currentStep', 1)
        ->call('goToStep', 2)
        ->assertSet('currentStep', 2)
        ->call('goToStep', 3)
        ->assertSet('currentStep', 3);
});

it('shows Claude configuration instructions on step 2', function () {
    Livewire::test(GetStarted::class)
        ->call('goToStep', 2)
        ->assertSee('Configure Claude Code')
        ->assertSee('MCP server configuration');
});

it('shows starter prompts on step 3', function () {
    Livewire::test(GetStarted::class)
        ->call('goToStep', 3)
        ->assertSee('Start Your AI Intake')
        ->assertSee('Try saying something like');
});

it('generates correct MCP endpoint URL', function () {
    $component = new GetStarted;

    expect($component->getMcpEndpoint())->toContain('/mcp/workout');
});

it('generates config JSON with placeholder for guests', function () {
    $component = new GetStarted;
    $json = $component->getConfigJson();

    expect($json)
        ->toContain('YOUR_API_KEY_HERE')
        ->toContain('mcp-remote')
        ->toContain('mcpServers');
});

it('prevents creating more than 5 tokens', function () {
    $user = User::factory()->create();

    // Create 5 tokens
    for ($i = 1; $i <= 5; $i++) {
        $user->createToken("Token {$i}");
    }

    Livewire::actingAs($user)
        ->test(GetStarted::class)
        ->set('tokenName', 'Sixth Token')
        ->call('createToken')
        ->assertHasErrors(['tokenLimit']);

    expect($user->tokens()->count())->toBe(5);
});
