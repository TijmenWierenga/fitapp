<?php

use App\Livewire\Chat\ConversationList;
use App\Models\AgentConversation;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('shows user conversations', function () {
    $user = User::factory()->create();

    AgentConversation::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'user_id' => $user->id,
        'title' => 'My workout plan',
    ]);

    Livewire::actingAs($user)
        ->test(ConversationList::class)
        ->assertSee('My workout plan');
});

it('hides other users conversations', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    AgentConversation::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'user_id' => $otherUser->id,
        'title' => 'Secret conversation',
    ]);

    Livewire::actingAs($user)
        ->test(ConversationList::class)
        ->assertDontSee('Secret conversation');
});

it('filters conversations by search', function () {
    $user = User::factory()->create();

    AgentConversation::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'user_id' => $user->id,
        'title' => 'Leg day plan',
    ]);

    AgentConversation::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'user_id' => $user->id,
        'title' => 'Running schedule',
    ]);

    Livewire::actingAs($user)
        ->test(ConversationList::class)
        ->set('search', 'Leg')
        ->assertSee('Leg day plan')
        ->assertDontSee('Running schedule');
});

it('deletes a conversation', function () {
    $user = User::factory()->create();

    $conversation = AgentConversation::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'user_id' => $user->id,
        'title' => 'To be deleted',
    ]);

    Livewire::actingAs($user)
        ->test(ConversationList::class)
        ->call('deleteConversation', $conversation->id);

    $this->assertDatabaseMissing('agent_conversations', [
        'id' => $conversation->id,
    ]);
});

it('cannot delete other users conversations', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $conversation = AgentConversation::create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'user_id' => $otherUser->id,
        'title' => 'Not mine',
    ]);

    Livewire::actingAs($user)
        ->test(ConversationList::class)
        ->call('deleteConversation', $conversation->id)
        ->assertForbidden();
});
