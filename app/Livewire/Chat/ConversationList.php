<?php

namespace App\Livewire\Chat;

use App\Livewire\Chat\Concerns\HasMessageLimits;
use App\Models\AgentConversation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ConversationList extends Component
{
    use HasMessageLimits;

    public string $search = '';

    public ?string $activeConversationId = null;

    /**
     * @return Collection<int, AgentConversation>
     */
    #[Computed]
    #[On('conversation-updated')]
    public function conversations(): Collection
    {
        if ($this->search !== '') {
            return AgentConversation::search($this->search)
                ->where('user_id', auth()->id())
                ->orderBy('updated_at', 'desc')
                ->get();
        }

        return AgentConversation::query()
            ->where('user_id', auth()->id())
            ->orderByDesc('updated_at')
            ->get();
    }

    #[On('conversation-updated')]
    public function refreshUsage(): void
    {
        $this->clearMessageLimitCache();
    }

    public function deleteConversation(string $id): void
    {
        $conversation = AgentConversation::findOrFail($id);

        Gate::authorize('delete', $conversation);

        $conversation->messages()->delete();
        $conversation->delete();

        if ($this->activeConversationId === $id) {
            $this->redirect(route('coach'), navigate: true);
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.chat.conversation-list');
    }
}
