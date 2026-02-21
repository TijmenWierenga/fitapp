<?php

use App\Livewire\Chat\Coach;
use App\Models\AgentConversation;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the coach page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('coach'))
        ->assertOk()
        ->assertSeeLivewire(Coach::class);
});

it('renders with a conversation id', function () {
    $user = User::factory()->create();
    $conversation = AgentConversation::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'user_id' => $user->id,
        'title' => 'Test conversation',
    ]);

    Livewire::actingAs($user)
        ->test(Coach::class, ['conversation' => $conversation])
        ->assertSet('conversationId', $conversation->id)
        ->assertOk();
});

it('denies access to other users conversations', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $conversation = AgentConversation::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'user_id' => $otherUser->id,
        'title' => 'Private conversation',
    ]);

    $this->actingAs($user)
        ->get(route('coach.conversation', $conversation))
        ->assertForbidden();
});

it('requires authentication', function () {
    $this->get(route('coach'))
        ->assertRedirect(route('login'));
});
