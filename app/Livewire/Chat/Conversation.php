<?php

namespace App\Livewire\Chat;

use App\Ai\Agents\FitnessCoach;
use App\Livewire\Chat\Concerns\HasMessageLimits;
use App\Models\AgentConversationMessage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\ToolCall;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Conversation extends Component
{
    use HasMessageLimits;

    #[Locked]
    public ?string $conversationId = null;

    #[Validate('required|string|max:2000')]
    public string $message = '';

    public string $pendingMessage = '';

    public string $streamedResponse = '';

    public bool $isStreaming = false;

    /**
     * @return Collection<int, AgentConversationMessage>
     */
    #[Computed]
    public function conversationMessages(): Collection
    {
        if (! $this->conversationId) {
            return collect();
        }

        return AgentConversationMessage::query()
            ->where('conversation_id', $this->conversationId)
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at')
            ->get();
    }

    public function submitPrompt(): void
    {
        $this->validate();

        if ($this->remainingMessages <= 0) {
            return;
        }

        $this->pendingMessage = $this->message;
        $this->reset('message');
        $this->streamedResponse = '';

        $this->js('$wire.ask()');
    }

    public function ask(): void
    {
        set_time_limit(120);

        $this->isStreaming = true;

        $agent = FitnessCoach::make();

        if ($this->conversationId) {
            $agent = $agent->continue($this->conversationId, auth()->user());
        } else {
            $agent = $agent->forUser(auth()->user());
        }

        $responseBuffer = '';
        /** @var array<int, array{label: string, icon: string, status: string}> $toolCalls */
        $toolCalls = [];
        $hasToolCalls = false;

        $agent->stream($this->pendingMessage)
            ->each(function (object $event) use (&$responseBuffer, &$toolCalls, &$hasToolCalls): void {
                if ($event instanceof TextDelta) {
                    $responseBuffer .= $event->delta;

                    if ($hasToolCalls && count($toolCalls) > 0 && $toolCalls[array_key_last($toolCalls)]['status'] === 'in_progress') {
                        $toolCalls[array_key_last($toolCalls)]['status'] = 'completed';
                        $this->stream(to: 'toolCallsData', content: json_encode($toolCalls), replace: true);
                    }

                    $this->stream(
                        to: 'streamedResponse',
                        content: Str::markdown($responseBuffer),
                        replace: true,
                    );
                }

                if ($event instanceof ToolCall) {
                    if (count($toolCalls) > 0) {
                        $toolCalls[array_key_last($toolCalls)]['status'] = 'completed';
                    }

                    $toolCalls[] = [
                        'label' => $this->toolLabel($event->toolCall->name),
                        'icon' => $this->toolIcon($event->toolCall->name),
                        'status' => 'in_progress',
                    ];

                    $hasToolCalls = true;

                    $this->stream(to: 'toolCallsData', content: json_encode($toolCalls), replace: true);

                    $responseBuffer = '';
                    $this->stream(to: 'streamedResponse', content: '', replace: true);
                }
            })
            ->then(function () use ($agent, &$toolCalls): void {
                $this->conversationId ??= $agent->currentConversation();

                if (count($toolCalls) > 0) {
                    $completedCalls = array_map(
                        fn (array $tc): array => [...$tc, 'status' => 'completed'],
                        $toolCalls,
                    );

                    $lastMessage = AgentConversationMessage::query()
                        ->where('conversation_id', $this->conversationId)
                        ->where('role', 'assistant')
                        ->latest()
                        ->first();

                    if ($lastMessage) {
                        $meta = $lastMessage->meta;
                        $meta['tool_calls'] = $completedCalls;
                        $lastMessage->update(['meta' => $meta]);
                    }
                }

                $this->isStreaming = false;
                $this->pendingMessage = '';
                $this->streamedResponse = '';

                unset($this->conversationMessages);
                $this->clearMessageLimitCache();

                $this->dispatch('conversation-updated');
            });
    }

    public function useSuggestion(string $text): void
    {
        $this->message = $text;
        $this->submitPrompt();
    }

    private function toolLabel(string $name): string
    {
        return match ($name) {
            'CreateWorkoutTool' => 'Creating workout',
            'UpdateWorkoutTool' => 'Updating workout',
            'DeleteWorkoutTool' => 'Deleting workout',
            'GetWorkoutTool' => 'Loading workout',
            'ListWorkoutsTool' => 'Loading workouts',
            'CompleteWorkoutTool' => 'Completing workout',
            'ExportWorkoutTool' => 'Exporting workout',
            'SearchExercisesTool' => 'Searching exercises',
            'UpdateFitnessProfileTool' => 'Updating your profile',
            'AddInjuryTool' => 'Adding injury record',
            'UpdateInjuryTool' => 'Updating injury',
            'RefreshUserContextTool' => 'Refreshing your data',
            default => 'Working',
        };
    }

    private function toolIcon(string $name): string
    {
        return match ($name) {
            'SearchExercisesTool' => 'magnifying-glass',
            'CreateWorkoutTool' => 'plus-circle',
            'GetWorkoutTool' => 'document-text',
            'ListWorkoutsTool' => 'list-bullet',
            'CompleteWorkoutTool' => 'check-circle',
            'ExportWorkoutTool' => 'arrow-down-tray',
            'UpdateFitnessProfileTool' => 'pencil-square',
            'AddInjuryTool' => 'plus',
            'UpdateInjuryTool' => 'pencil',
            'RefreshUserContextTool' => 'arrow-path',
            default => 'cog-6-tooth',
        };
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.chat.conversation');
    }
}
