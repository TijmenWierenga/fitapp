<div class="absolute inset-0 flex">
    {{-- Conversation list sidebar --}}
    <div class="hidden lg:flex lg:w-72 xl:w-80 flex-col border-e border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900">
        <livewire:chat.conversation-list :activeConversationId="$conversationId" />
    </div>

    {{-- Chat area --}}
    <div class="flex-1 flex flex-col min-w-0 min-h-0">
        <livewire:chat.conversation :conversationId="$conversationId" />
    </div>
</div>
