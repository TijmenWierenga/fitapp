<div class="flex flex-col h-full">
    {{-- Top bar --}}
    <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
        <div class="flex items-center gap-2">
            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-lime-600">
                <flux:icon.sparkles class="size-4 text-white" />
            </div>
            <flux:heading size="sm">{{ __('Fitness Coach') }}</flux:heading>
        </div>

        @if ($this->remainingMessages <= 10)
            <flux:badge size="sm" color="zinc">
                {{ trans_choice(':count message remaining|:count messages remaining', $this->remainingMessages, ['count' => $this->remainingMessages]) }}
            </flux:badge>
        @endif
    </div>

    {{-- Messages area --}}
    <div
        class="flex-1 overflow-y-auto px-4 py-6 space-y-6"
        x-data="{ shouldAutoScroll: true }"
        x-ref="messageContainer"
        @scroll="shouldAutoScroll = ($refs.messageContainer.scrollTop + $refs.messageContainer.clientHeight) >= ($refs.messageContainer.scrollHeight - 100)"
        x-effect="if (shouldAutoScroll) $refs.messageContainer.scrollTop = $refs.messageContainer.scrollHeight"
    >
        <div class="max-w-3xl mx-auto space-y-6">
            {{-- Empty state with suggestions --}}
            @if ($this->conversationMessages->isEmpty() && ! $isStreaming && ! $pendingMessage)
                <div class="flex flex-col items-center justify-center py-12">
                    <div class="flex items-center justify-center w-16 h-16 rounded-full bg-lime-600/10 mb-6">
                        <flux:icon.sparkles class="size-8 text-lime-600" />
                    </div>
                    <flux:heading size="lg" class="mb-2">{{ __('Hi! I\'m your fitness coach.') }}</flux:heading>
                    <flux:text class="mb-8 text-center max-w-md">{{ __('I can help you plan workouts, track progress, manage injuries, and reach your fitness goals.') }}</flux:text>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full max-w-lg">
                        <button wire:click="useSuggestion('Help me build a workout for today')" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 text-left hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                            <flux:icon.fire class="size-5 text-orange-500 flex-none" />
                            <div>
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Build a workout') }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Plan a session for today') }}</div>
                            </div>
                        </button>

                        <button wire:click="useSuggestion('Show me my training progress this week')" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 text-left hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                            <flux:icon.chart-bar class="size-5 text-blue-500 flex-none" />
                            <div>
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Track my progress') }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Review your training week') }}</div>
                            </div>
                        </button>

                        <button wire:click="useSuggestion('How should I handle recovery today?')" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 text-left hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                            <flux:icon.heart class="size-5 text-red-500 flex-none" />
                            <div>
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Recovery tips') }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Get recovery advice') }}</div>
                            </div>
                        </button>

                        <button wire:click="useSuggestion('What does my current training load look like?')" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 text-left hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                            <flux:icon.bolt class="size-5 text-yellow-500 flex-none" />
                            <div>
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ __('Check my workload') }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Review training load') }}</div>
                            </div>
                        </button>
                    </div>
                </div>
            @endif

            {{-- Persisted messages --}}
            @foreach ($this->conversationMessages as $msg)
                @if ($msg->role === 'user')
                    <div class="flex justify-end">
                        <div class="max-w-[80%] rounded-2xl rounded-tr-sm bg-lime-600 px-4 py-3 text-white">
                            <div class="text-sm whitespace-pre-wrap">{{ $msg->content }}</div>
                        </div>
                    </div>
                @elseif ($msg->role === 'assistant' && $msg->content)
                    <div class="flex gap-3">
                        <div class="flex-none flex items-start">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-lime-600">
                                <flux:icon.sparkles class="size-4 text-white" />
                            </div>
                        </div>
                        <div class="flex-1 min-w-0 space-y-2">
                            {{-- Stored tool calls --}}
                            @if (count($msg->toolCalls) > 0)
                                @php $tools = $msg->toolCalls; $lastTool = end($tools); @endphp
                                <div x-data="{ expanded: false }" class="rounded-xl overflow-hidden border border-zinc-700/50">
                                    <button
                                        type="button"
                                        @click="expanded = !expanded"
                                        class="flex items-center gap-2.5 w-full px-3.5 py-2.5 text-left bg-indigo-950/60 hover:bg-indigo-950/80 transition-colors"
                                    >
                                        <flux:icon.check-circle class="size-3.5 text-indigo-400 flex-none" />
                                        <span class="text-xs font-mono font-medium text-indigo-400 truncate">{{ $lastTool['label'] }}</span>
                                        @if (count($msg->toolCalls) > 1)
                                            <span class="text-[11px] font-mono text-indigo-400/50">+{{ count($msg->toolCalls) - 1 }}</span>
                                        @endif
                                        <flux:icon.chevron-down
                                            class="size-3.5 text-zinc-600 ml-auto flex-none transition-transform duration-200"
                                            ::class="expanded && 'rotate-180'"
                                        />
                                    </button>
                                    <div x-show="expanded" x-collapse x-cloak class="bg-indigo-950/30">
                                        @foreach ($msg->toolCalls as $tool)
                                            <div class="flex items-center gap-2.5 px-3.5 py-2 border-t border-zinc-800/40">
                                                <flux:icon :name="$tool['icon'] ?? 'cog-6-tooth'" class="size-3.5 text-indigo-400/60 flex-none" />
                                                <span class="text-xs font-mono text-zinc-500">{{ $tool['label'] }}</span>
                                                <flux:icon.check-circle class="size-3 text-indigo-400/40 ml-auto flex-none" />
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @elseif ($msg->thinking)
                                {{-- Legacy thinking block --}}
                                <div x-data="{ expanded: false }" class="rounded-2xl rounded-tl-sm bg-zinc-800/50 dark:bg-zinc-900/60 px-4 py-2.5">
                                    <button
                                        type="button"
                                        @click="expanded = !expanded"
                                        class="flex items-center gap-2 w-full text-left"
                                    >
                                        <flux:icon.sparkles class="size-3.5 text-lime-500 flex-none" />
                                        <span class="text-xs font-medium font-mono text-lime-500">{{ __('Thinking') }}</span>
                                        <flux:icon.chevron-down
                                            class="size-3.5 text-zinc-500 ml-auto transition-transform"
                                            ::class="expanded && 'rotate-180'"
                                        />
                                    </button>
                                    <div x-show="expanded" x-collapse x-cloak class="mt-2">
                                        <div class="text-xs font-mono text-zinc-400 dark:text-zinc-500 whitespace-pre-wrap">{{ $msg->thinking }}</div>
                                    </div>
                                </div>
                            @endif

                            <div class="rounded-2xl rounded-tl-sm bg-zinc-100 dark:bg-zinc-800 px-4 py-3">
                                <div class="prose prose-sm prose-zinc dark:prose-invert prose-lime max-w-none">
                                    {!! \Illuminate\Support\Str::markdown($msg->content) !!}
                                </div>
                            </div>
                            <div class="mt-1 text-xs text-zinc-400">
                                {{ $msg->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach

            {{-- Pending user message --}}
            @if ($pendingMessage)
                <div class="flex justify-end">
                    <div class="max-w-[80%] rounded-2xl rounded-tr-sm bg-lime-600 px-4 py-3 text-white">
                        <div class="text-sm whitespace-pre-wrap">{{ $pendingMessage }}</div>
                    </div>
                </div>
            @endif

            {{-- Assistant streaming response --}}
            @if ($pendingMessage || $isStreaming || $streamedResponse)
                <div class="flex gap-3">
                    <div class="flex-none flex items-start">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full bg-lime-600">
                            <flux:icon.sparkles class="size-4 text-white" />
                        </div>
                    </div>
                    <div
                        class="flex-1 min-w-0 space-y-2"
                        x-data="{
                            toolCalls: [],
                            expanded: false,
                            hasResponse: false,
                            get latestTool() { return this.toolCalls[this.toolCalls.length - 1] ?? null },
                            init() {
                                new MutationObserver(() => {
                                    try { this.toolCalls = JSON.parse(this.$refs.toolData.textContent) } catch {}
                                }).observe(this.$refs.toolData, { childList: true, characterData: true, subtree: true });

                                new MutationObserver(() => {
                                    this.hasResponse = this.$refs.responseEl.textContent.trim().length > 0;
                                }).observe(this.$refs.responseEl, { childList: true, characterData: true, subtree: true });
                            }
                        }"
                    >
                        {{-- Hidden JSON stream target --}}
                        <span x-ref="toolData" wire:stream="toolCallsData" class="hidden"></span>

                        {{-- Tool activity section --}}
                        <template x-if="toolCalls.length > 0">
                            <div
                                class="rounded-xl overflow-hidden border transition-colors duration-300"
                                :class="latestTool?.status === 'in_progress' ? 'border-indigo-500/30' : 'border-zinc-700/50'"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                            >
                                {{-- Header: latest tool --}}
                                <button
                                    type="button"
                                    @click="expanded = !expanded"
                                    class="flex items-center gap-2.5 w-full px-3.5 py-2.5 text-left bg-indigo-950/60 hover:bg-indigo-950/80 transition-colors"
                                >
                                    {{-- Pulsing dot for in-progress --}}
                                    <span x-show="latestTool?.status === 'in_progress'" class="relative flex-none size-3.5 flex items-center justify-center">
                                        <span class="absolute size-3 rounded-full bg-indigo-400/30 animate-ping"></span>
                                        <span class="relative size-2 rounded-full bg-indigo-400"></span>
                                    </span>
                                    {{-- Check for completed --}}
                                    <svg x-show="latestTool?.status !== 'in_progress'" x-cloak class="size-3.5 text-indigo-400 flex-none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.06l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/>
                                    </svg>

                                    {{-- Latest tool label --}}
                                    <span class="text-xs font-mono font-medium text-indigo-400 truncate" x-text="latestTool?.label"></span>

                                    {{-- Count badge --}}
                                    <span x-show="toolCalls.length > 1" x-cloak class="text-[11px] font-mono text-indigo-400/50" x-text="'+' + (toolCalls.length - 1)"></span>

                                    {{-- Chevron --}}
                                    <svg class="size-3.5 text-zinc-600 ml-auto flex-none transition-transform duration-200" :class="expanded && 'rotate-180'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                                    </svg>
                                </button>

                                {{-- Expanded: all tool cards --}}
                                <div x-show="expanded" x-collapse x-cloak class="bg-indigo-950/30">
                                    <template x-for="(tool, i) in toolCalls" :key="i">
                                        <div
                                            class="flex items-center gap-2.5 px-3.5 py-2 border-t border-zinc-800/40"
                                            :style="{ animation: 'toolFadeIn 150ms ease-out both', animationDelay: (i * 40) + 'ms' }"
                                        >
                                            <span
                                                class="size-1.5 rounded-full flex-none transition-colors duration-300"
                                                :class="tool.status === 'in_progress' ? 'bg-indigo-400 animate-pulse' : 'bg-indigo-400/40'"
                                            ></span>
                                            <span
                                                class="text-xs font-mono truncate transition-colors duration-300"
                                                :class="tool.status === 'in_progress' ? 'text-indigo-300' : 'text-zinc-500'"
                                                x-text="tool.label"
                                            ></span>
                                            {{-- Completed check --}}
                                            <svg x-show="tool.status === 'completed'" x-cloak class="size-3 text-indigo-400/40 ml-auto flex-none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.06l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/>
                                            </svg>
                                            {{-- In-progress spinner --}}
                                            <svg x-show="tool.status === 'in_progress'" x-cloak class="size-3 text-indigo-400 ml-auto flex-none animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4Zm2 5.291A7.962 7.962 0 0 1 4 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647Z"/>
                                            </svg>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        {{-- Response bubble --}}
                        <div class="rounded-2xl rounded-tl-sm bg-zinc-100 dark:bg-zinc-800 px-4 py-3">
                            <div
                                x-ref="responseEl"
                                wire:stream="streamedResponse"
                                class="prose prose-sm prose-zinc dark:prose-invert prose-lime max-w-none"
                                :class="! hasResponse && 'hidden'"
                            ></div>
                            <div x-show="! hasResponse" class="flex items-center gap-1.5 py-0.5">
                                <span class="size-1.5 rounded-full bg-zinc-400 dark:bg-zinc-500 animate-bounce" style="animation-delay: 0ms"></span>
                                <span class="size-1.5 rounded-full bg-zinc-400 dark:bg-zinc-500 animate-bounce" style="animation-delay: 150ms"></span>
                                <span class="size-1.5 rounded-full bg-zinc-400 dark:bg-zinc-500 animate-bounce" style="animation-delay: 300ms"></span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Input area --}}
    <div class="border-t border-zinc-200 dark:border-zinc-700 px-4 py-3">
        <div class="max-w-3xl mx-auto">
            <form wire:submit="submitPrompt">
                <flux:composer
                    wire:model="message"
                    placeholder="{{ __('Ask your coach anything...') }}"
                    submit="enter"
                    rows="1"
                    max-rows="4"
                    :disabled="$isStreaming || $this->remainingMessages <= 0"
                >
                    <x-slot name="actionsTrailing">
                        <flux:button
                            type="submit"
                            size="sm"
                            variant="primary"
                            icon="paper-airplane"
                            :disabled="! $message || $isStreaming"
                        />
                    </x-slot>
                </flux:composer>
            </form>

            @if ($this->remainingMessages <= 0)
                <flux:text size="xs" class="mt-2 text-center text-zinc-400">
                    {{ __('Daily message limit reached. Come back tomorrow!') }}
                </flux:text>
            @endif
        </div>
    </div>

    <style>
        @keyframes toolFadeIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</div>
