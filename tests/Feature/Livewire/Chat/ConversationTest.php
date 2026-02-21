<?php

use App\Ai\Agents\FitnessCoach;
use App\Livewire\Chat\Conversation;
use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the conversation component', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Conversation::class)
        ->assertOk();
});

it('shows empty state suggestions when no conversation', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Conversation::class)
        ->assertSee('I\'m your fitness coach')
        ->assertSee('Build a workout')
        ->assertSee('Track my progress')
        ->assertSee('Recovery tips')
        ->assertSee('Check my workload');
});

it('loads persisted messages for an existing conversation', function () {
    $user = User::factory()->create();
    $conversationId = (string) \Illuminate\Support\Str::uuid();

    AgentConversation::create([
        'id' => $conversationId,
        'user_id' => $user->id,
        'title' => 'Test',
    ]);

    AgentConversationMessage::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'conversation_id' => $conversationId,
        'user_id' => $user->id,
        'agent' => FitnessCoach::class,
        'role' => 'user',
        'content' => 'Plan me a workout',
    ]);

    AgentConversationMessage::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'conversation_id' => $conversationId,
        'user_id' => $user->id,
        'agent' => FitnessCoach::class,
        'role' => 'assistant',
        'content' => 'Here is your workout plan!',
    ]);

    Livewire::actingAs($user)
        ->test(Conversation::class, ['conversationId' => $conversationId])
        ->assertSee('Plan me a workout')
        ->assertSee('Here is your workout plan!');
});

it('validates message is required', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Conversation::class)
        ->set('message', '')
        ->call('submitPrompt')
        ->assertHasErrors(['message' => 'required']);
});

it('validates message max length', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Conversation::class)
        ->set('message', str_repeat('a', 2001))
        ->call('submitPrompt')
        ->assertHasErrors(['message' => 'max']);
});

it('enforces daily message limit', function () {
    $user = User::factory()->create();

    // Create 50 messages for today
    $conversationId = (string) \Illuminate\Support\Str::uuid();
    AgentConversation::create([
        'id' => $conversationId,
        'user_id' => $user->id,
        'title' => 'Busy day',
    ]);

    for ($i = 0; $i < 50; $i++) {
        AgentConversationMessage::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'agent' => FitnessCoach::class,
            'role' => 'user',
            'content' => "Message {$i}",
        ]);
    }

    $component = Livewire::actingAs($user)
        ->test(Conversation::class);

    expect($component->get('remainingMessages'))->toBe(0);
});

it('sets pending message on submit and clears input', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Conversation::class)
        ->set('message', 'Help me build a workout')
        ->call('submitPrompt')
        ->assertSet('pendingMessage', 'Help me build a workout')
        ->assertSet('message', '');
});

it('uses suggestion to set message and submit', function () {
    FitnessCoach::fake(['Great suggestion!']);

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Conversation::class)
        ->call('useSuggestion', 'Help me build a workout for today')
        ->assertSet('pendingMessage', 'Help me build a workout for today')
        ->assertSet('message', '');
});

it('renders tool calls for stored messages with tool_calls in meta', function () {
    $user = User::factory()->create();
    $conversationId = (string) \Illuminate\Support\Str::uuid();

    AgentConversation::create([
        'id' => $conversationId,
        'user_id' => $user->id,
        'title' => 'Test',
    ]);

    AgentConversationMessage::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'conversation_id' => $conversationId,
        'user_id' => $user->id,
        'agent' => FitnessCoach::class,
        'role' => 'user',
        'content' => 'Build me a workout',
    ]);

    AgentConversationMessage::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'conversation_id' => $conversationId,
        'user_id' => $user->id,
        'agent' => FitnessCoach::class,
        'role' => 'assistant',
        'content' => 'Here is your workout!',
        'meta' => [
            'tool_calls' => [
                ['label' => 'Checking your workload', 'icon' => 'chart-bar', 'status' => 'completed'],
                ['label' => 'Searching exercises', 'icon' => 'magnifying-glass', 'status' => 'completed'],
            ],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(Conversation::class, ['conversationId' => $conversationId])
        ->assertSee('Searching exercises')
        ->assertSee('+1')
        ->assertSee('Here is your workout!');
});

it('renders legacy thinking block for stored messages with thinking in meta', function () {
    $user = User::factory()->create();
    $conversationId = (string) \Illuminate\Support\Str::uuid();

    AgentConversation::create([
        'id' => $conversationId,
        'user_id' => $user->id,
        'title' => 'Test',
    ]);

    AgentConversationMessage::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'conversation_id' => $conversationId,
        'user_id' => $user->id,
        'agent' => FitnessCoach::class,
        'role' => 'assistant',
        'content' => 'Here is your workout!',
        'meta' => ['thinking' => "Checking your workload...\n- Loading workouts..."],
    ]);

    Livewire::actingAs($user)
        ->test(Conversation::class, ['conversationId' => $conversationId])
        ->assertSee('Thinking')
        ->assertSee('Here is your workout!');
});

it('does not render tool calls or thinking for plain messages', function () {
    $user = User::factory()->create();
    $conversationId = (string) \Illuminate\Support\Str::uuid();

    AgentConversation::create([
        'id' => $conversationId,
        'user_id' => $user->id,
        'title' => 'Test',
    ]);

    AgentConversationMessage::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'conversation_id' => $conversationId,
        'user_id' => $user->id,
        'agent' => FitnessCoach::class,
        'role' => 'assistant',
        'content' => 'Just a simple reply',
    ]);

    Livewire::actingAs($user)
        ->test(Conversation::class, ['conversationId' => $conversationId])
        ->assertSee('Just a simple reply')
        ->assertDontSeeHtml('bg-indigo-950')
        ->assertDontSeeHtml('font-mono text-lime-500');
});

it('returns toolCalls from meta accessor on AgentConversationMessage', function () {
    $message = new AgentConversationMessage;
    $message->meta = [
        'tool_calls' => [
            ['label' => 'Checking your workload', 'icon' => 'chart-bar', 'status' => 'completed'],
        ],
    ];

    expect($message->toolCalls)->toHaveCount(1);
    expect($message->toolCalls[0]['label'])->toBe('Checking your workload');
});

it('returns empty array for toolCalls when meta has no tool_calls key', function () {
    $message = new AgentConversationMessage;

    expect($message->toolCalls)->toBe([]);
});

it('returns thinking from meta accessor on AgentConversationMessage', function () {
    $message = new AgentConversationMessage;
    $message->meta = ['thinking' => 'Loading workouts...'];

    expect($message->thinking)->toBe('Loading workouts...');
});

it('returns null thinking when meta has no thinking key', function () {
    $message = new AgentConversationMessage;

    expect($message->thinking)->toBeNull();
});
