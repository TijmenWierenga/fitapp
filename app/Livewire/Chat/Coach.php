<?php

namespace App\Livewire\Chat;

use App\Models\AgentConversation;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app-full')]
class Coach extends Component
{
    public ?string $conversationId = null;

    public function mount(?AgentConversation $conversation = null): void
    {
        if ($conversation?->exists) {
            Gate::authorize('view', $conversation);
            $this->conversationId = $conversation->id;
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.chat.coach');
    }
}
