@props(['note'])

@if($note->content)
    <div class="pl-6 pb-2">
        <div class="prose prose-sm prose-zinc dark:prose-invert max-w-none text-zinc-600 dark:text-zinc-400">
            {!! Str::markdown($note->content, ['html_input' => 'escape']) !!}
        </div>
    </div>
@endif
