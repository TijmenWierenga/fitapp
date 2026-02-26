<?php

use App\Ai\Agents\FitnessCoach;
use App\Livewire\Chat\Conversation;
use App\Livewire\Chat\ConversationList;
use App\Models\AgentConversation;
use App\Models\AgentConversationMessage;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function createUserMessages(User $user, int $count, ?string $conversationId = null, ?Carbon\CarbonImmutable $at = null): string
{
    $conversationId ??= (string) \Illuminate\Support\Str::uuid();

    if (! AgentConversation::find($conversationId)) {
        AgentConversation::create([
            'id' => $conversationId,
            'user_id' => $user->id,
            'title' => 'Test',
        ]);
    }

    for ($i = 0; $i < $count; $i++) {
        $message = AgentConversationMessage::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'agent' => FitnessCoach::class,
            'role' => 'user',
            'content' => "Message {$i}",
        ]);

        if ($at) {
            $message->forceFill(['created_at' => $at])->saveQuietly();
        }
    }

    return $conversationId;
}

it('auto-sends intake message when intake is true and user has no conversations', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Conversation::class, ['intake' => true])
        ->assertSet('pendingMessage', 'I just signed up! Help me set up my fitness profile and plan my first workout.');
});

it('does not auto-send intake when intake is false', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Conversation::class, ['intake' => false])
        ->assertSet('pendingMessage', '')
        ->assertSet('message', '');
});

it('does not auto-send intake when user has existing conversations', function () {
    $user = User::factory()->create();

    AgentConversation::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'user_id' => $user->id,
        'title' => 'Previous chat',
    ]);

    Livewire::actingAs($user)
        ->test(Conversation::class, ['intake' => true])
        ->assertSet('pendingMessage', '')
        ->assertSet('message', '');
});

it('does not auto-send intake when conversationId is set', function () {
    $user = User::factory()->create();
    $conversationId = (string) \Illuminate\Support\Str::uuid();

    AgentConversation::create([
        'id' => $conversationId,
        'user_id' => $user->id,
        'title' => 'Existing conversation',
    ]);

    Livewire::actingAs($user)
        ->test(Conversation::class, ['intake' => true, 'conversationId' => $conversationId])
        ->assertSet('pendingMessage', '')
        ->assertSet('message', '');
});

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
        ->assertSee('Ready to crush your goals?')
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

it('enforces daily message limit within rolling 24h window', function () {
    $user = User::factory()->create();

    $limit = config('ai.coach.daily_message_limit');
    createUserMessages($user, $limit);

    $component = Livewire::actingAs($user)
        ->test(Conversation::class);

    expect($component->get('remainingDailyMessages'))->toBe(0)
        ->and($component->get('remainingMessages'))->toBe(0);
});

it('enforces monthly message limit within rolling 30d window', function () {
    $user = User::factory()->create();

    $limit = config('ai.coach.monthly_message_limit');
    createUserMessages($user, $limit);

    $component = Livewire::actingAs($user)
        ->test(Conversation::class);

    expect($component->instance()->remainingMonthlyMessages)->toBe(0)
        ->and($component->instance()->remainingMessages)->toBe(0);
});

it('monthly limit takes priority when lower than daily remaining', function () {
    $user = User::factory()->create();

    $monthlyLimit = config('ai.coach.monthly_message_limit');
    $dailyLimit = config('ai.coach.daily_message_limit');
    $todayMessages = 5;
    $olderMessages = $monthlyLimit - $todayMessages;

    // Create older messages (2 days ago — outside 24h window but inside 30d window)
    $conversationId = createUserMessages(
        $user,
        $olderMessages,
        at: now()->subDays(2)->toImmutable(),
    );

    // Create today's messages (within 24h window)
    createUserMessages($user, $todayMessages, $conversationId);

    $component = Livewire::actingAs($user)
        ->test(Conversation::class);

    // Daily has plenty left (25 - 5 = 20), but monthly is exhausted (100 - 100 = 0)
    expect($component->instance()->remainingDailyMessages)->toBe($dailyLimit - $todayMessages)
        ->and($component->instance()->remainingMonthlyMessages)->toBe(0)
        ->and($component->instance()->remainingMessages)->toBe(0);
});

it('resets daily limit after rolling 24h window expires', function () {
    $user = User::factory()->create();

    $limit = config('ai.coach.daily_message_limit');

    // Create messages 25 hours ago
    createUserMessages($user, $limit, at: now()->subHours(25)->toImmutable());

    $component = Livewire::actingAs($user)
        ->test(Conversation::class);

    // All messages are outside the 24h window — full limit available
    expect($component->instance()->usedDailyMessages)->toBe(0)
        ->and($component->instance()->remainingDailyMessages)->toBe($limit)
        ->and($component->instance()->dailyWindowStart)->toBeNull();
});

it('resets monthly limit after rolling 30d window expires', function () {
    $user = User::factory()->create();

    $limit = config('ai.coach.monthly_message_limit');

    // Create messages 31 days ago
    createUserMessages($user, $limit, at: now()->subDays(31)->toImmutable());

    $component = Livewire::actingAs($user)
        ->test(Conversation::class);

    // All messages are outside the 30d window — full limit available
    expect($component->instance()->usedMonthlyMessages)->toBe(0)
        ->and($component->instance()->remainingMonthlyMessages)->toBe($limit)
        ->and($component->instance()->monthlyWindowStart)->toBeNull();
});

it('returns dailyWindowStart as the oldest message within 24h', function () {
    $user = User::factory()->create();

    $this->travelTo(now()->startOfDay()->addHours(14));

    createUserMessages($user, 3);

    $component = Livewire::actingAs($user)
        ->test(Conversation::class);

    expect($component->instance()->dailyWindowStart)->not->toBeNull()
        ->and($component->instance()->dailyWindowStart->toDateTimeString())
        ->toBe(now()->startOfDay()->addHours(14)->toDateTimeString());
});

it('returns null dailyWindowStart when no messages exist', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Conversation::class);

    expect($component->instance()->dailyWindowStart)->toBeNull();
});

it('returns formatted dailyResetIn string', function () {
    $user = User::factory()->create();

    $this->travelTo(now()->startOfDay()->addHours(10));

    createUserMessages($user, 1);

    // Travel forward 18 hours (6h remaining in the 24h window)
    $this->travelTo(now()->startOfDay()->addHours(28));

    $component = Livewire::actingAs($user)
        ->test(Conversation::class);

    expect($component->instance()->dailyResetIn)->toBe('6h 0m');
});

it('returns null dailyResetIn when no active window', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Conversation::class);

    expect($component->instance()->dailyResetIn)->toBeNull();
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

it('exposes daily and monthly limits as computed properties', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(Conversation::class);

    expect($component->instance()->dailyMessageLimit)->toBe(25)
        ->and($component->instance()->monthlyMessageLimit)->toBe(100);
});

it('computes used daily messages correctly', function () {
    $user = User::factory()->create();
    createUserMessages($user, 7);

    $component = Livewire::actingAs($user)
        ->test(Conversation::class);

    expect($component->instance()->usedDailyMessages)->toBe(7)
        ->and($component->instance()->usedMonthlyMessages)->toBe(7);
});

it('shows error banner when daily limit exhausted', function () {
    $user = User::factory()->create();

    $limit = config('ai.coach.daily_message_limit');
    createUserMessages($user, $limit);

    Livewire::actingAs($user)
        ->test(Conversation::class)
        ->assertSee('Daily message limit reached')
        ->assertSee("all {$limit} messages for today");
});

it('shows error banner when monthly limit exhausted', function () {
    $user = User::factory()->create();

    $monthlyLimit = config('ai.coach.monthly_message_limit');
    createUserMessages($user, $monthlyLimit);

    Livewire::actingAs($user)
        ->test(Conversation::class)
        ->assertSee('Monthly message limit reached')
        ->assertSee("all {$monthlyLimit} messages for this period");
});

it('shows warning banner when running low on messages', function () {
    $user = User::factory()->create();

    $limit = config('ai.coach.daily_message_limit');
    createUserMessages($user, $limit - 5);

    Livewire::actingAs($user)
        ->test(Conversation::class)
        ->assertSee('5 daily messages remaining');
});

it('shows no banner when plenty of messages remaining', function () {
    $user = User::factory()->create();

    createUserMessages($user, 3);

    Livewire::actingAs($user)
        ->test(Conversation::class)
        ->assertDontSee('message limit reached')
        ->assertDontSee('messages remaining');
});

it('renders usage panel in conversation list sidebar', function () {
    $user = User::factory()->create();
    createUserMessages($user, 5);

    Livewire::actingAs($user)
        ->test(ConversationList::class)
        ->assertSee('Daily messages')
        ->assertSee('Monthly messages')
        ->assertSee('5 / 25')
        ->assertSee('5 / 100');
});

it('refreshes usage in conversation list on conversation-updated event', function () {
    $user = User::factory()->create();
    createUserMessages($user, 3);

    $component = Livewire::actingAs($user)
        ->test(ConversationList::class);

    expect($component->instance()->usedDailyMessages)->toBe(3);

    // Create more messages
    createUserMessages($user, 2);

    // Dispatch event to trigger refresh
    $component->dispatch('conversation-updated');

    expect($component->instance()->usedDailyMessages)->toBe(5);
});
