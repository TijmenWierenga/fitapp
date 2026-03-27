<div class="max-w-2xl mx-auto py-6">
    <div class="flex items-center gap-2.5 px-6 py-5 border-b border-zinc-200 dark:border-zinc-700">
        <flux:button href="{{ route('dashboard') }}" variant="ghost" icon="arrow-left" size="sm" />
        <span class="text-base font-semibold text-zinc-900 dark:text-white">Import Garmin Activity</span>
    </div>

    @if(session('error'))
        <div class="px-6 py-4">
            <flux:callout variant="danger">
                <flux:callout.heading>Error</flux:callout.heading>
                <flux:callout.text>{{ session('error') }}</flux:callout.text>
            </flux:callout>
        </div>
    @endif

    {{-- Step 1: Upload --}}
    @if($step === 'upload')
        <div class="px-6 py-6 space-y-4">
            <flux:file-upload wire:model="fitFile" label="Upload .FIT file" accept=".fit">
                <flux:file-upload.dropzone
                    heading="Drop your .FIT file here or click to browse"
                    text="Garmin activity files (.FIT) up to 10MB"
                />
            </flux:file-upload>

            @if($fitFile)
                <flux:file-item
                    :heading="$fitFile->getClientOriginalName()"
                    :size="$fitFile->getSize()"
                />
            @endif

            @if($parseError)
                <flux:callout variant="danger">
                    <flux:callout.heading>Invalid file</flux:callout.heading>
                    <flux:callout.text>{{ $parseError }}</flux:callout.text>
                </flux:callout>
            @endif
        </div>
    @endif

    {{-- Step 2: Preview --}}
    @if($step === 'preview' && $preview)
        <div class="px-6 py-6 space-y-6">
            {{-- Activity summary --}}
            <div class="space-y-3">
                <flux:heading size="sm">Activity Summary</flux:heading>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-lg bg-zinc-100 dark:bg-zinc-800 px-3 py-2">
                        <div class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 uppercase">Activity</div>
                        <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $preview['activity'] }}</div>
                    </div>

                    <div class="rounded-lg bg-zinc-100 dark:bg-zinc-800 px-3 py-2">
                        <div class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 uppercase">Date</div>
                        <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $preview['date'] }}</div>
                    </div>

                    @if($preview['duration'])
                        <div class="rounded-lg bg-zinc-100 dark:bg-zinc-800 px-3 py-2">
                            <div class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 uppercase">Duration</div>
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $preview['duration'] }}</div>
                        </div>
                    @endif

                    @if($preview['distance'])
                        <div class="rounded-lg bg-zinc-100 dark:bg-zinc-800 px-3 py-2">
                            <div class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 uppercase">Distance</div>
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $preview['distance'] }}</div>
                        </div>
                    @endif

                    @if($preview['calories'])
                        <div class="rounded-lg bg-zinc-100 dark:bg-zinc-800 px-3 py-2">
                            <div class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 uppercase">Calories</div>
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $preview['calories'] }} kcal</div>
                        </div>
                    @endif

                    @if($preview['avgHeartRate'])
                        <div class="rounded-lg bg-zinc-100 dark:bg-zinc-800 px-3 py-2">
                            <div class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 uppercase">Avg HR</div>
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $preview['avgHeartRate'] }} bpm</div>
                        </div>
                    @endif

                    @if($preview['setCount'] > 0)
                        <div class="rounded-lg bg-zinc-100 dark:bg-zinc-800 px-3 py-2">
                            <div class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 uppercase">Sets</div>
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $preview['setCount'] }} active sets</div>
                        </div>
                    @endif

                    @if($preview['lapCount'] > 0)
                        <div class="rounded-lg bg-zinc-100 dark:bg-zinc-800 px-3 py-2">
                            <div class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 uppercase">Laps</div>
                            <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $preview['lapCount'] }} laps</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Duplicate warning --}}
            @if($duplicateWarning)
                <flux:callout variant="warning">
                    <flux:callout.heading>Possible duplicate</flux:callout.heading>
                    <flux:callout.text>{{ $duplicateWarning }}</flux:callout.text>
                </flux:callout>
            @endif

            {{-- Matching workouts --}}
            @if($matchingWorkouts && count($matchingWorkouts) > 0 && $selectedWorkoutId === null)
                <div class="space-y-3">
                    <flux:heading size="sm">Matching Planned Workouts</flux:heading>
                    <flux:text size="sm">We found planned workouts that match this activity. Select one to merge data, or create as a new workout.</flux:text>

                    <div class="space-y-2">
                        @foreach($matchingWorkouts as $match)
                            <button
                                wire:click="selectWorkout({{ $match['id'] }})"
                                class="w-full text-left rounded-lg border border-zinc-200 dark:border-zinc-700 px-4 py-3 hover:border-accent transition-colors"
                            >
                                <div class="font-medium text-sm text-zinc-900 dark:text-white">{{ $match['name'] }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $match['activity'] }} &middot; {{ $match['scheduled_at'] }}</div>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Selected workout indicator --}}
            @if($selectedWorkoutId)
                @php
                    $selected = collect($matchingWorkouts ?? [])->firstWhere('id', $selectedWorkoutId);
                @endphp
                @if($selected)
                    <flux:callout variant="info">
                        <flux:callout.heading>Merging into: {{ $selected['name'] }}</flux:callout.heading>
                        <flux:callout.text>{{ $selected['activity'] }} &middot; {{ $selected['scheduled_at'] }}</flux:callout.text>
                    </flux:callout>
                @elseif($workout)
                    <flux:callout variant="info">
                        <flux:callout.heading>Merging into pre-selected workout</flux:callout.heading>
                    </flux:callout>
                @endif
            @endif

            {{-- Actions --}}
            <div class="flex items-center gap-3">
                @if($selectedWorkoutId)
                    <flux:button variant="primary" wire:click="mergeWithSelected">Merge Into Workout</flux:button>
                @endif
                <flux:button variant="{{ $selectedWorkoutId ? 'ghost' : 'primary' }}" wire:click="createAsNew">Create as New Workout</flux:button>
                <flux:button variant="ghost" wire:click="resetImport">Cancel</flux:button>
            </div>
        </div>
    @endif

    {{-- Step 3: Map Exercises --}}
    @if($step === 'map')
        <div class="px-6 py-6 space-y-6">
            <div class="space-y-1">
                <flux:heading size="sm">Map Exercises</flux:heading>
                <flux:text size="sm">Assign exercises from your library to enable workload tracking. You can skip any.</flux:text>
            </div>

            <div class="space-y-3">
                @foreach($exerciseGroups as $group)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 px-4 py-3 space-y-2">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="font-medium text-zinc-900 dark:text-white">Exercise {{ $group['index'] + 1 }}</span>
                            <span class="text-zinc-400">&middot;</span>
                            <span class="text-zinc-500 dark:text-zinc-400">{{ $group['sets'] }} sets &middot; {{ $group['reps'] }} &middot; {{ $group['weight'] }}</span>
                        </div>

                        <flux:select
                            wire:model="exerciseMappings.{{ $group['index'] }}"
                            variant="combobox"
                            :filter="false"
                            placeholder="Search exercise..."
                            clearable
                        >
                            <x-slot name="input">
                                <flux:select.input wire:model.live.debounce.300ms="exerciseSearch" />
                            </x-slot>

                            @foreach($this->searchResults as $exercise)
                                <flux:select.option :value="$exercise->id" wire:key="map-{{ $group['index'] }}-{{ $exercise->id }}">
                                    {{ $exercise->name }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                @endforeach
            </div>

            <div class="flex items-center gap-3">
                <flux:button variant="primary" wire:click="proceedToEvaluate">Continue</flux:button>
                <flux:button variant="ghost" wire:click="proceedToEvaluate">Skip All</flux:button>
            </div>
        </div>
    @endif

    {{-- Step 4: Evaluate --}}
    @if($step === 'evaluate')
        <div class="px-6 py-6 space-y-6">
            <flux:heading size="sm">How did it go?</flux:heading>
            <flux:text size="sm">Rate your effort and how you felt (optional).</flux:text>

            <div class="grid grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>RPE (1-10)</flux:label>
                    <flux:input type="number" wire:model="rpe" min="1" max="10" placeholder="Rate of Perceived Exertion" />
                    <flux:error name="rpe" />
                </flux:field>

                <flux:field>
                    <flux:label>Feeling (1-5)</flux:label>
                    <flux:input type="number" wire:model="feeling" min="1" max="5" placeholder="How did you feel?" />
                    <flux:error name="feeling" />
                </flux:field>
            </div>

            <div class="flex items-center gap-3">
                <flux:button variant="primary" wire:click="confirmImport">Import Activity</flux:button>
                <flux:button variant="ghost" wire:click="resetImport">Cancel</flux:button>
            </div>
        </div>
    @endif

    {{-- Step 5: Result --}}
    @if($step === 'result' && $importResultData)
        <div class="px-6 py-6 space-y-6">
            <flux:callout variant="success">
                <flux:callout.heading>Import successful</flux:callout.heading>
                <flux:callout.text>"{{ $importResultData['workout_name'] }}" has been imported.</flux:callout.text>
            </flux:callout>

            @if(count($importResultData['matched']) > 0)
                <div class="space-y-2">
                    <flux:heading size="sm">Matched Exercises ({{ count($importResultData['matched']) }})</flux:heading>
                    <ul class="text-sm text-zinc-600 dark:text-zinc-400 space-y-1">
                        @foreach($importResultData['matched'] as $name)
                            <li class="flex items-center gap-1.5">
                                <flux:icon.check class="size-3.5 text-green-500" />
                                {{ $name }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(count($importResultData['unmatched']) > 0)
                <div class="space-y-2">
                    <flux:heading size="sm">Unmatched Exercises ({{ count($importResultData['unmatched']) }})</flux:heading>
                    <ul class="text-sm text-zinc-600 dark:text-zinc-400 space-y-1">
                        @foreach($importResultData['unmatched'] as $name)
                            <li class="flex items-center gap-1.5">
                                <flux:icon.exclamation-triangle class="size-3.5 text-amber-500" />
                                {{ $name }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(count($importResultData['warnings']) > 0)
                <div class="space-y-2">
                    <flux:heading size="sm">Warnings</flux:heading>
                    <ul class="text-sm text-zinc-500 dark:text-zinc-400 space-y-1">
                        @foreach($importResultData['warnings'] as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex items-center gap-3">
                <flux:button variant="primary" wire:click="goToWorkout">View Workout</flux:button>
                <flux:button variant="ghost" wire:click="resetImport">Import Another</flux:button>
            </div>
        </div>
    @endif
</div>
