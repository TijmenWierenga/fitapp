<div class="max-w-4xl mx-auto p-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <flux:heading size="xl">{{ __('Injuries') }}</flux:heading>

        <flux:button variant="primary" icon="plus" icon:variant="outline" wire:click="openLogModal">
            {{ __('Log Injury') }}
        </flux:button>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-4 mb-8 sm:grid-cols-3">
        {{-- Active --}}
        <flux:card class="flex items-center gap-4">
            <div class="p-2 rounded-lg bg-red-100 dark:bg-red-900/30">
                <flux:icon name="exclamation-triangle" class="text-red-500 dark:text-red-400" />
            </div>
            <div>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Active Injuries') }}</flux:text>
                <div class="text-2xl font-bold">{{ $this->activeCount }}</div>
            </div>
        </flux:card>

        {{-- Recovering --}}
        <flux:card class="flex items-center gap-4">
            <div class="p-2 rounded-lg bg-amber-100 dark:bg-amber-900/30">
                <flux:icon name="arrow-path" class="text-amber-500 dark:text-amber-400" />
            </div>
            <div>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Recovering') }}</flux:text>
                <div class="text-2xl font-bold">{{ $this->recoveringCount }}</div>
            </div>
        </flux:card>

        {{-- Healed --}}
        <flux:card class="flex items-center gap-4">
            <div class="p-2 rounded-lg bg-green-100 dark:bg-green-900/30">
                <flux:icon name="check-circle" class="text-green-500 dark:text-green-400" />
            </div>
            <div>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Healed') }}</flux:text>
                <div class="text-2xl font-bold">{{ $this->healedCount }}</div>
            </div>
        </flux:card>
    </div>

    {{-- Injuries Table --}}
    @if($this->injuries->isNotEmpty())
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Body Part') }}</flux:table.column>
                <flux:table.column class="hidden sm:table-cell">{{ __('Injury Type') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Severity') }}</flux:table.column>
                <flux:table.column class="hidden md:table-cell">{{ __('Date Injured') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach($this->injuries as $injury)
                    <flux:table.row :key="$injury->id">
                        <flux:table.cell class="font-medium">
                            {{ $injury->body_part->label() }}
                            @if($injury->side && $injury->side !== \App\Enums\Side::NotApplicable)
                                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">
                                    ({{ $injury->side->label() }})
                                </flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="hidden sm:table-cell">
                            {{ $injury->injury_type->label() }}
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">
                            @if($injury->severity)
                                <flux:badge :color="$injury->severity->color()" size="sm">
                                    {{ $injury->severity->label() }}
                                </flux:badge>
                            @else
                                <flux:text class="text-zinc-400">&mdash;</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="hidden md:table-cell">
                            {{ $injury->started_at->format('M j, Y') }}
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($injury->is_active)
                                <flux:badge color="red" size="sm">{{ __('Active') }}</flux:badge>
                            @elseif($injury->ended_at->greaterThanOrEqualTo(now()->subDays(30)))
                                <flux:badge color="amber" size="sm">{{ __('Recovering') }}</flux:badge>
                            @else
                                <flux:badge color="green" size="sm">{{ __('Healed') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            <flux:dropdown>
                                <flux:button variant="ghost" size="sm" icon="ellipsis-horizontal" />
                                <flux:menu>
                                    <flux:menu.item icon="eye" :href="route('injuries.show', $injury)" wire:navigate>
                                        {{ __('View') }}
                                    </flux:menu.item>
                                    <flux:menu.item icon="pencil" wire:click="openEditModal({{ $injury->id }})">
                                        {{ __('Edit') }}
                                    </flux:menu.item>
                                    <flux:menu.item
                                        icon="trash"
                                        variant="danger"
                                        wire:click="deleteInjury({{ $injury->id }})"
                                        wire:confirm="{{ __('Are you sure you want to delete this injury? This action cannot be undone.') }}"
                                    >
                                        {{ __('Delete') }}
                                    </flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @else
        <flux:callout variant="info" icon="information-circle">
            {{ __('No injuries recorded. Log an injury to start tracking your recovery.') }}
        </flux:callout>
    @endif

    {{-- Log / Edit Injury Modal --}}
    <flux:modal
        name="injury-modal"
        wire:model="showInjuryModal"
        class="max-w-lg"
    >
        <form wire:submit="saveInjury" class="space-y-6">
            <x-modal-icon
                icon="heart"
                :heading="$editingInjuryId ? __('Edit Injury') : __('Log New Injury')"
            />

            {{-- Body Part --}}
            <flux:field>
                <flux:label>{{ __('Body Part') }}</flux:label>
                <flux:select wire:model="bodyPart" required searchable>
                    <flux:select.option value="">{{ __('Select body part...') }}</flux:select.option>
                    @foreach ($this->bodyPartsGrouped as $region => $parts)
                        @foreach ($parts as $part)
                            <flux:select.option :value="$part->value">
                                {{ $region }} - {{ $part->label() }}
                            </flux:select.option>
                        @endforeach
                    @endforeach
                </flux:select>
                <flux:error name="bodyPart" />
            </flux:field>

            {{-- Side --}}
            <flux:field>
                <flux:label>{{ __('Side') }}</flux:label>
                <flux:radio.group wire:model="side" variant="segmented">
                    @foreach ($this->sides as $sideOption)
                        <flux:radio :value="$sideOption->value" :label="$sideOption->label()" />
                    @endforeach
                </flux:radio.group>
                <flux:error name="side" />
            </flux:field>

            {{-- Injury Type --}}
            <flux:field>
                <flux:label>{{ __('Injury Type') }}</flux:label>
                <flux:select wire:model="injuryType" required>
                    <flux:select.option value="">{{ __('Select type...') }}</flux:select.option>
                    @foreach ($this->injuryTypes as $type)
                        <flux:select.option :value="$type->value">
                            {{ $type->label() }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="injuryType" />
            </flux:field>

            {{-- Severity --}}
            <flux:field>
                <flux:label>{{ __('Severity') }}</flux:label>
                <flux:radio.group wire:model="severity" variant="segmented">
                    @foreach ($this->severities as $severityOption)
                        <flux:radio :value="$severityOption->value" :label="$severityOption->label()" />
                    @endforeach
                </flux:radio.group>
                <flux:error name="severity" />
            </flux:field>

            {{-- Date of Injury --}}
            <flux:field>
                <flux:label>{{ __('Date of Injury') }}</flux:label>
                <flux:input type="date" wire:model="startedAt" required />
                <flux:error name="startedAt" />
            </flux:field>

            {{-- How it happened --}}
            <flux:field>
                <flux:label>{{ __('How it happened') }}</flux:label>
                <flux:textarea
                    wire:model="howItHappened"
                    rows="2"
                    :placeholder="__('e.g., Tweaked my shoulder during bench press...')"
                />
                <flux:error name="howItHappened" />
            </flux:field>

            {{-- Current Symptoms --}}
            <flux:field>
                <flux:label>{{ __('Current Symptoms') }}</flux:label>
                <flux:textarea
                    wire:model="currentSymptoms"
                    rows="2"
                    :placeholder="__('e.g., Sharp pain when lifting overhead...')"
                />
                <flux:error name="currentSymptoms" />
            </flux:field>

            {{-- Pain Level --}}
            <flux:field>
                <flux:label>{{ __('Pain Level') }}</flux:label>
                <flux:description>{{ __('Rate your current pain from 1 (minimal) to 10 (worst)') }}</flux:description>
                <div class="mt-3">
                    <div class="flex justify-between gap-1">
                        @foreach(range(1, 10) as $value)
                            <button
                                type="button"
                                wire:click="$set('painLevel', {{ $value }})"
                                class="flex-1 py-2 text-sm font-medium rounded-md transition-colors {{ $painLevel === $value ? 'bg-accent text-accent-foreground' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' }}"
                            >
                                {{ $value }}
                            </button>
                        @endforeach
                    </div>
                    <div class="flex justify-between mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                        <span>{{ __('Minimal') }}</span>
                        <span>{{ __('Moderate') }}</span>
                        <span>{{ __('Worst') }}</span>
                    </div>
                </div>
            </flux:field>

            {{-- Edit-only fields --}}
            @if($editingInjuryId)
                <flux:separator />

                {{-- Status Update --}}
                <flux:field>
                    <flux:label>{{ __('Status Update') }}</flux:label>
                    <flux:radio.group wire:model="statusUpdate" variant="segmented">
                        <flux:radio value="active" label="{{ __('Active') }}" />
                        <flux:radio value="recovering" label="{{ __('Recovering') }}" />
                        <flux:radio value="healed" label="{{ __('Healed') }}" />
                    </flux:radio.group>
                    <flux:error name="statusUpdate" />
                </flux:field>

                {{-- Recovery Notes --}}
                <flux:field>
                    <flux:label>{{ __('Recovery Notes') }}</flux:label>
                    <flux:textarea
                        wire:model="injuryNotes"
                        rows="2"
                        :placeholder="__('Notes about recovery progress...')"
                    />
                    <flux:error name="injuryNotes" />
                </flux:field>

                {{-- Danger Zone --}}
                <flux:separator />

                <div class="space-y-3">
                    <flux:heading size="sm" class="!text-red-600 dark:!text-red-400">{{ __('Danger Zone') }}</flux:heading>
                    <div class="flex gap-2">
                        <flux:button
                            type="button"
                            variant="filled"
                            size="sm"
                            wire:click="markAsHealed({{ $editingInjuryId }})"
                        >
                            {{ __('Mark as Healed') }}
                        </flux:button>
                        <flux:button
                            type="button"
                            variant="danger"
                            size="sm"
                            wire:click="deleteInjury({{ $editingInjuryId }})"
                            wire:confirm="{{ __('Are you sure you want to delete this injury? This action cannot be undone.') }}"
                        >
                            {{ __('Delete Injury') }}
                        </flux:button>
                    </div>
                </div>
            @endif

            {{-- Footer --}}
            <div class="flex gap-3">
                <flux:button type="button" variant="outline" class="flex-1" wire:click="closeModal">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary" class="flex-1">
                    {{ $editingInjuryId ? __('Save Changes') : __('Log Injury') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
