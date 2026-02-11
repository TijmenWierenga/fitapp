<div class="max-w-4xl mx-auto p-6">
    {{-- Header --}}
    <div class="flex items-center gap-4 mb-6">
        <flux:button href="{{ route('fitness-profile.edit') }}" variant="ghost" icon="arrow-left" />
        <div class="flex-1">
            <flux:heading size="xl">{{ $injury->body_part->label() }}</flux:heading>
            <flux:subheading>
                {{ $injury->injury_type->label() }} &middot;
                {{ $injury->started_at->toDateString() }}
                @if($injury->ended_at)
                    &mdash; {{ $injury->ended_at->toDateString() }}
                @else
                    &mdash; {{ __('Present') }}
                @endif
            </flux:subheading>
        </div>
        @if($injury->is_active)
            <flux:badge color="red">{{ __('Active') }}</flux:badge>
        @else
            <flux:badge color="green">{{ __('Resolved') }}</flux:badge>
        @endif
    </div>

    {{-- Reports --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <flux:heading size="sm">{{ __('Reports') }}</flux:heading>
            <flux:button
                variant="primary"
                icon="plus"
                icon:variant="outline"
                wire:click="openReportModal"
            >
                {{ __('Add Report') }}
            </flux:button>
        </div>

        @if($this->reports->isNotEmpty())
            <div class="space-y-4">
                @foreach($this->reports as $report)
                    <flux:card :key="$report->id" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <flux:badge size="sm" color="zinc">{{ $report->type->label() }}</flux:badge>
                                <flux:text class="text-xs text-zinc-500">
                                    {{ $report->reported_at->toDateString() }}
                                </flux:text>
                            </div>
                            <flux:button
                                variant="ghost"
                                size="sm"
                                icon="trash"
                                wire:click="deleteReport({{ $report->id }})"
                                wire:confirm="{{ __('Are you sure you want to delete this report?') }}"
                            />
                        </div>
                        <div class="prose prose-sm prose-zinc dark:prose-invert max-w-none text-zinc-600 dark:text-zinc-400">
                            @if (str_starts_with(trim($report->content), '<'))
                                {!! Str::markdown($report->content, ['html_input' => 'allow']) !!}
                            @else
                                {!! Str::markdown($report->content, ['html_input' => 'escape']) !!}
                            @endif
                        </div>
                    </flux:card>
                @endforeach
            </div>
        @else
            <flux:callout variant="info" icon="information-circle">
                {{ __('No reports yet. Add a report to track your recovery progress.') }}
            </flux:callout>
        @endif
    </div>

    {{-- Add Report Modal --}}
    <flux:modal
        name="report-modal"
        wire:model="showReportModal"
        class="max-w-2xl"
    >
        <form wire:submit="saveReport" class="space-y-6">
            <x-modal-icon
                icon="document-text"
                :heading="__('Add Report')"
            />

            <flux:field>
                <flux:label>{{ __('Report Type') }}</flux:label>
                <flux:select wire:model="reportType" required>
                    <flux:select.option value="">{{ __('Select type...') }}</flux:select.option>
                    @foreach ($this->reportTypes as $type)
                        <flux:select.option :value="$type->value">
                            {{ $type->label() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="reportType"/>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Date') }}</flux:label>
                <flux:input type="date" wire:model="reportedAt" required />
                <flux:description>{{ __('When did this happen?') }}</flux:description>
                <flux:error name="reportedAt"/>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Content') }}</flux:label>
                <flux:editor
                    wire:model="reportContent"
                    :placeholder="__('Describe your update, PT visit notes, or milestone...')"
                    toolbar="heading | bold italic strike | bullet ordered blockquote | link"
                />
                <flux:error name="reportContent"/>
            </flux:field>

            <div class="flex gap-3">
                <flux:button type="button" variant="outline" class="flex-1" wire:click="closeReportModal">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary" class="flex-1">
                    {{ __('Add Report') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
