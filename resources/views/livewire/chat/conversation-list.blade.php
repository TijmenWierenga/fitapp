<div class="flex flex-col h-full">
    {{-- Header --}}
    <div class="p-3 flex items-center gap-2">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search conversations...') }}" icon="magnifying-glass" size="sm" class="flex-1" />

        <flux:button href="{{ route('coach') }}" variant="primary" icon="plus" size="sm" square wire:navigate />
    </div>

    {{-- Conversation items --}}
    <div class="flex-1 overflow-y-auto px-1.5">
        @forelse ($this->conversations as $conversation)
            <div class="group relative">
                <a
                    href="{{ route('coach.conversation', $conversation) }}"
                    wire:navigate
                    @class([
                        'block rounded-lg px-3 py-2.5 transition-colors',
                        'bg-zinc-200 dark:bg-zinc-700' => $activeConversationId === $conversation->id,
                        'hover:bg-zinc-100 dark:hover:bg-zinc-800' => $activeConversationId !== $conversation->id,
                    ])
                >
                    <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                        {{ $conversation->title ?: __('New conversation') }}
                    </div>
                    <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ $conversation->updated_at->diffForHumans() }}
                    </div>
                </a>

                <div class="absolute right-2 top-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <flux:dropdown>
                        <flux:button variant="ghost" size="xs" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item
                                variant="danger"
                                icon="trash"
                                wire:click="deleteConversation('{{ $conversation->id }}')"
                                wire:confirm="{{ __('Delete this conversation?') }}"
                            >
                                {{ __('Delete') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
            </div>
        @empty
            <div class="px-3 py-8 text-center text-sm text-zinc-400 dark:text-zinc-500">
                {{ $search ? __('No conversations found.') : __('No conversations yet.') }}
            </div>
        @endforelse
    </div>

    {{-- Usage panel --}}
    @php
        $dailyLimit = $this->dailyMessageLimit;
        $monthlyLimit = $this->monthlyMessageLimit;
        $usedDaily = $this->usedDailyMessages;
        $usedMonthly = $this->usedMonthlyMessages;
        $dailyExhausted = $this->remainingDailyMessages <= 0;
        $monthlyExhausted = $this->remainingMonthlyMessages <= 0;
        $dailyPct = $dailyLimit > 0 ? min(($usedDaily / $dailyLimit) * 100, 100) : 0;
        $monthlyPct = $monthlyLimit > 0 ? min(($usedMonthly / $monthlyLimit) * 100, 100) : 0;
    @endphp
    <div class="border-t border-zinc-700 px-3.5 py-3 space-y-3">
        {{-- Daily --}}
        <div class="space-y-1.5">
            <div class="flex justify-between items-baseline">
                <span class="text-xs text-zinc-400">{{ __('Daily messages') }}</span>
                <span @class(['text-xs font-medium', 'text-red-400' => $dailyExhausted, 'text-white' => ! $dailyExhausted])>{{ $usedDaily }} / {{ $dailyLimit }}</span>
            </div>
            <div class="h-1.5 rounded-full bg-zinc-700 overflow-hidden">
                <div
                    @class(['h-full rounded-full transition-all duration-300', 'bg-red-500' => $dailyExhausted, 'bg-lime-500' => ! $dailyExhausted])
                    style="width: {{ $dailyPct }}%"
                ></div>
            </div>
        </div>

        {{-- Monthly --}}
        <div class="space-y-1.5">
            <div class="flex justify-between items-baseline">
                <span class="text-xs text-zinc-400">{{ __('Monthly messages') }}</span>
                <span @class(['text-xs font-medium', 'text-red-400' => $monthlyExhausted, 'text-white' => ! $monthlyExhausted])>{{ $usedMonthly }} / {{ $monthlyLimit }}</span>
            </div>
            <div class="h-1.5 rounded-full bg-zinc-700 overflow-hidden">
                <div
                    @class(['h-full rounded-full transition-all duration-300', 'bg-red-500' => $monthlyExhausted, 'bg-lime-500' => ! $monthlyExhausted])
                    style="width: {{ $monthlyPct }}%"
                ></div>
            </div>
        </div>

        {{-- Reset info --}}
        @if ($this->dailyResetIn)
            <div class="flex items-center gap-1.5">
                <flux:icon.clock class="size-3 text-zinc-500" />
                <span class="text-[11px] text-zinc-500">{{ __('Daily limit resets in :time', ['time' => $this->dailyResetIn]) }}</span>
            </div>
        @endif
    </div>
</div>
