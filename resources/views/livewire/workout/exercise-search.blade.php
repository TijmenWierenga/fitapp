<div>
    @if($showModal)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
            x-data
            x-on:keydown.escape.window="$wire.closeModal()"
        >
            <div class="w-full max-w-md mx-4 bg-zinc-900 rounded-2xl shadow-2xl border border-zinc-800 flex flex-col max-h-[85vh]">

                {{-- ===== SEARCH STEP ===== --}}
                @if($step === 'search')
                    {{-- Header --}}
                    <div class="flex items-center justify-between px-5 pt-5 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex items-center justify-center size-8 rounded-lg bg-lime-400/10">
                                <flux:icon.book-open class="size-4 text-lime-400" />
                            </div>
                            <flux:heading size="lg" class="text-white">Exercise Catalogue</flux:heading>
                        </div>
                        <button wire:click="closeModal" class="text-zinc-500 hover:text-zinc-300 transition-colors">
                            <flux:icon.x-mark class="size-5" />
                        </button>
                    </div>

                    {{-- Search input --}}
                    <div class="px-5 pb-3">
                        <div class="relative">
                            <flux:icon.magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-zinc-500" />
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="query"
                                placeholder="Search exercises..."
                                class="w-full pl-10 pr-4 py-2.5 bg-zinc-800 border border-zinc-700 rounded-lg text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-lime-400/50 focus:ring-1 focus:ring-lime-400/25 transition-colors"
                            />
                        </div>
                    </div>

                    {{-- Muscle group filter pills --}}
                    <div class="px-5 pb-3 flex gap-1.5 overflow-x-auto scrollbar-none">
                        <button
                            wire:click="setMuscleGroupFilter(null)"
                            @class([
                                'shrink-0 px-3 py-1 rounded-full text-xs font-medium transition-colors',
                                'bg-lime-400 text-zinc-900' => $muscleGroupFilter === null,
                                'bg-zinc-800 text-zinc-400 hover:bg-zinc-700 hover:text-zinc-300' => $muscleGroupFilter !== null,
                            ])
                        >
                            All
                        </button>
                        @foreach($this->muscleGroups as $mg)
                            <button
                                wire:click="setMuscleGroupFilter('{{ $mg->name }}')"
                                @class([
                                    'shrink-0 px-3 py-1 rounded-full text-xs font-medium transition-colors',
                                    'bg-lime-400 text-zinc-900' => $muscleGroupFilter === $mg->name,
                                    'bg-zinc-800 text-zinc-400 hover:bg-zinc-700 hover:text-zinc-300' => $muscleGroupFilter !== $mg->name,
                                ])
                            >
                                {{ $mg->label }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Exercise list --}}
                    <div class="flex-1 overflow-y-auto min-h-0 border-t border-zinc-800">
                        @if($this->exercises->isEmpty())
                            {{-- No results state --}}
                            <div class="flex flex-col items-center justify-center py-12 px-6">
                                <div class="flex items-center justify-center size-12 rounded-full bg-zinc-800 mb-4">
                                    <flux:icon.magnifying-glass class="size-5 text-zinc-500" />
                                </div>
                                <p class="text-white font-semibold text-sm mb-1">No exercises found</p>
                                <p class="text-zinc-500 text-xs text-center mb-5">Try a different search or add a custom exercise</p>
                                <button
                                    wire:click="goToFreeForm"
                                    class="flex items-center gap-2 px-5 py-2.5 bg-lime-400 text-zinc-900 rounded-lg text-sm font-semibold hover:bg-lime-300 transition-colors"
                                >
                                    <flux:icon.pencil class="size-4" />
                                    Add as free-form exercise
                                </button>
                            </div>
                        @else
                            <div class="divide-y divide-zinc-800/50">
                                @foreach($this->exercises as $exercise)
                                    <div class="flex items-center gap-3 px-5 py-3 hover:bg-zinc-800/50 transition-colors group">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-white truncate">{{ $exercise->name }}</p>
                                            <p class="text-xs text-zinc-500 truncate">
                                                {{ $exercise->muscleGroups->pluck('label')->join(', ') }}
                                            </p>
                                        </div>
                                        @if($exercise->equipment)
                                            <span class="shrink-0 px-2 py-0.5 text-[10px] font-medium text-zinc-400 bg-zinc-800 rounded border border-zinc-700">
                                                {{ ucfirst($exercise->equipment) }}
                                            </span>
                                        @endif
                                        <button
                                            wire:click="selectExercise({{ $exercise->id }})"
                                            class="shrink-0 flex items-center justify-center size-7 rounded-md bg-lime-400/10 text-lime-400 hover:bg-lime-400 hover:text-zinc-900 transition-colors"
                                        >
                                            <flux:icon.plus class="size-4" />
                                        </button>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Can't find card --}}
                            <div class="border-t border-zinc-800">
                                <button
                                    wire:click="goToFreeForm"
                                    class="flex items-center gap-3 w-full px-5 py-3.5 hover:bg-zinc-800/50 transition-colors text-left"
                                >
                                    <div class="flex items-center justify-center size-8 rounded-lg bg-lime-400/10">
                                        <flux:icon.pencil class="size-4 text-lime-400" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-white">Can't find your exercise?</p>
                                        <p class="text-xs text-zinc-500">Add it as free-form text</p>
                                    </div>
                                    <flux:icon.chevron-right class="size-4 text-zinc-600" />
                                </button>
                            </div>
                        @endif
                    </div>

                    {{-- Footer count --}}
                    <div class="px-5 py-3 border-t border-zinc-800">
                        <p class="text-xs text-zinc-600 text-center">{{ $this->exercises->count() }} exercises found</p>
                    </div>

                {{-- ===== FREE-FORM STEP ===== --}}
                @elseif($step === 'freeform')
                    {{-- Header --}}
                    <div class="flex items-center justify-between px-5 pt-5 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex items-center justify-center size-8 rounded-lg bg-lime-400/10">
                                <flux:icon.pencil class="size-4 text-lime-400" />
                            </div>
                            <flux:heading size="lg" class="text-white">Add Custom Exercise</flux:heading>
                        </div>
                        <button wire:click="closeModal" class="text-zinc-500 hover:text-zinc-300 transition-colors">
                            <flux:icon.x-mark class="size-5" />
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-5 pb-4 space-y-5">
                        {{-- Exercise name --}}
                        <div>
                            <label class="block text-xs font-medium text-zinc-400 mb-1.5">Exercise Name</label>
                            <input
                                type="text"
                                wire:model="selectedName"
                                placeholder="e.g. Cable Face Pull"
                                class="w-full px-3 py-2.5 bg-zinc-800 border border-zinc-700 rounded-lg text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-lime-400/50 focus:ring-1 focus:ring-lime-400/25 transition-colors"
                            />
                            @error('selectedName')
                                <p class="text-xs text-red-400 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Muscle groups --}}
                        <div>
                            <label class="block text-[10px] font-semibold tracking-wider text-zinc-500 uppercase mb-2">Muscle Groups (optional)</label>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($this->muscleGroups as $mg)
                                    <button
                                        type="button"
                                        wire:click="toggleFreeFormMuscleGroup('{{ $mg->label }}')"
                                        @class([
                                            'px-3 py-1 rounded-full text-xs font-medium transition-colors border',
                                            'bg-lime-400/15 text-lime-400 border-lime-400/30' => in_array($mg->label, $freeFormMuscleGroups, true),
                                            'bg-zinc-800 text-zinc-400 border-zinc-700 hover:border-zinc-600' => ! in_array($mg->label, $freeFormMuscleGroups, true),
                                        ])
                                    >
                                        {{ $mg->label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Exercise type --}}
                        <div>
                            <label class="block text-xs font-medium text-zinc-400 mb-1.5">Exercise Type</label>
                            <select
                                wire:model="selectedType"
                                class="w-full px-3 py-2.5 bg-zinc-800 border border-zinc-700 rounded-lg text-sm text-white focus:outline-none focus:border-lime-400/50 focus:ring-1 focus:ring-lime-400/25 transition-colors appearance-none"
                            >
                                <option value="strength">Strength</option>
                                <option value="cardio">Cardio</option>
                                <option value="duration">Duration</option>
                            </select>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-end gap-3 px-5 py-4 border-t border-zinc-800">
                        <button
                            wire:click="backToSearch"
                            class="px-4 py-2 text-sm text-zinc-400 hover:text-white transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            wire:click="confirmFreeForm"
                            class="px-5 py-2 bg-lime-400 text-zinc-900 rounded-lg text-sm font-semibold hover:bg-lime-300 transition-colors"
                        >
                            Continue
                        </button>
                    </div>

                {{-- ===== CONFIGURE STEP ===== --}}
                @elseif($step === 'configure')
                    {{-- Header --}}
                    <div class="flex items-center justify-between px-5 pt-5 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex items-center justify-center size-8 rounded-lg bg-lime-400/10">
                                @if($selectedExerciseId)
                                    <flux:icon.cog-6-tooth class="size-4 text-lime-400" />
                                @else
                                    <flux:icon.pencil class="size-4 text-lime-400" />
                                @endif
                            </div>
                            <flux:heading size="lg" class="text-white">Configure Exercise</flux:heading>
                        </div>
                        <button wire:click="closeModal" class="text-zinc-500 hover:text-zinc-300 transition-colors">
                            <flux:icon.x-mark class="size-5" />
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-5 pb-4 space-y-5">
                        {{-- Exercise name --}}
                        <div>
                            <h3 class="text-lg font-bold text-white">{{ $selectedName }}</h3>

                            {{-- Muscle group badges --}}
                            @if(count($selectedMuscleGroups) > 0)
                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    @foreach($selectedMuscleGroups as $mg)
                                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-lime-400/15 text-lime-400 border border-lime-400/20">
                                            {{ $mg }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Catalogue/Custom indicator --}}
                            @if($selectedExerciseId)
                                <p class="text-xs text-zinc-500 mt-2 flex items-center gap-1">
                                    <flux:icon.book-open class="size-3" />
                                    View in catalogue
                                </p>
                            @else
                                <span class="inline-flex items-center gap-1 mt-2 px-2 py-0.5 rounded text-[10px] font-medium bg-zinc-800 text-zinc-400 border border-zinc-700">
                                    <flux:icon.pencil class="size-3" />
                                    Custom Exercise
                                </span>
                            @endif
                        </div>

                        {{-- Training parameters --}}
                        <div>
                            <p class="text-[10px] font-semibold tracking-wider text-zinc-500 uppercase mb-3">Training Parameters</p>

                            @if($selectedType === 'strength')
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs text-zinc-400 mb-1">Sets</label>
                                        <input type="number" wire:model="targetSets" placeholder="4" min="1"
                                            class="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-lg text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-lime-400/50 focus:ring-1 focus:ring-lime-400/25" />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-zinc-400 mb-1">Reps</label>
                                        <input type="number" wire:model="targetRepsMax" placeholder="8" min="0"
                                            class="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-lg text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-lime-400/50 focus:ring-1 focus:ring-lime-400/25" />
                                    </div>
                                    <div>
                                        <label class="block text-xs text-zinc-400 mb-1">Weight</label>
                                        <div class="relative">
                                            <input type="number" wire:model="targetWeight" placeholder="80" min="0" step="0.5"
                                                class="w-full px-3 py-2 pr-10 bg-zinc-800 border border-zinc-700 rounded-lg text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-lime-400/50 focus:ring-1 focus:ring-lime-400/25" />
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-zinc-500">kg</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-zinc-400 mb-1">Rest Period</label>
                                        <div class="relative">
                                            <input type="number" wire:model="restAfter" placeholder="90" min="0"
                                                class="w-full px-3 py-2 pr-8 bg-zinc-800 border border-zinc-700 rounded-lg text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-lime-400/50 focus:ring-1 focus:ring-lime-400/25" />
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-zinc-500">s</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="block text-xs text-zinc-400 mb-1">RPE / Intensity</label>
                                    <div class="relative">
                                        <input type="number" wire:model="targetRpe" placeholder="8" min="1" max="10" step="0.5"
                                            class="w-full px-3 py-2 pr-12 bg-zinc-800 border border-zinc-700 rounded-lg text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-lime-400/50 focus:ring-1 focus:ring-lime-400/25" />
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-zinc-500">1-10</span>
                                    </div>
                                </div>

                            @elseif($selectedType === 'cardio')
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs text-zinc-400 mb-1">Pace Min</label>
                                        <div class="relative">
                                            <input type="number" wire:model="targetPaceMin" placeholder="300" min="0"
                                                class="w-full px-3 py-2 pr-16 bg-zinc-800 border border-zinc-700 rounded-lg text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-lime-400/50 focus:ring-1 focus:ring-lime-400/25" />
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-zinc-500">s/km</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-zinc-400 mb-1">Pace Max</label>
                                        <div class="relative">
                                            <input type="number" wire:model="targetPaceMax" placeholder="330" min="0"
                                                class="w-full px-3 py-2 pr-16 bg-zinc-800 border border-zinc-700 rounded-lg text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-lime-400/50 focus:ring-1 focus:ring-lime-400/25" />
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-zinc-500">s/km</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="block text-xs text-zinc-400 mb-1">Heart Rate Zone</label>
                                    <select wire:model="targetHeartRateZone"
                                        class="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-lg text-sm text-white focus:outline-none focus:border-lime-400/50 focus:ring-1 focus:ring-lime-400/25 appearance-none">
                                        <option value="">Select zone...</option>
                                        <option value="1">Zone 1 — Recovery</option>
                                        <option value="2">Zone 2 — Aerobic</option>
                                        <option value="3">Zone 3 — Tempo</option>
                                        <option value="4">Zone 4 — Threshold</option>
                                        <option value="5">Zone 5 — VO2max</option>
                                    </select>
                                </div>
                                <div class="grid grid-cols-2 gap-3 mt-3">
                                    <div>
                                        <label class="block text-xs text-zinc-400 mb-1">Distance</label>
                                        <div class="relative">
                                            <input type="number" wire:model="targetDistance" placeholder="10000" min="0" step="1"
                                                class="w-full px-3 py-2 pr-8 bg-zinc-800 border border-zinc-700 rounded-lg text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-lime-400/50 focus:ring-1 focus:ring-lime-400/25" />
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-zinc-500">m</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-zinc-400 mb-1">Duration</label>
                                        <div class="relative">
                                            <input type="number" wire:model="targetDuration" placeholder="3600" min="0"
                                                class="w-full px-3 py-2 pr-8 bg-zinc-800 border border-zinc-700 rounded-lg text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-lime-400/50 focus:ring-1 focus:ring-lime-400/25" />
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-zinc-500">s</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <label class="block text-xs text-zinc-400 mb-1">RPE / Intensity</label>
                                    <div class="relative">
                                        <input type="number" wire:model="targetRpe" placeholder="5" min="1" max="10" step="0.5"
                                            class="w-full px-3 py-2 pr-12 bg-zinc-800 border border-zinc-700 rounded-lg text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-lime-400/50 focus:ring-1 focus:ring-lime-400/25" />
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-zinc-500">1-10</span>
                                    </div>
                                </div>

                            @elseif($selectedType === 'duration')
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs text-zinc-400 mb-1">Duration</label>
                                        <div class="relative">
                                            <input type="number" wire:model="targetDuration" placeholder="60" min="0"
                                                class="w-full px-3 py-2 pr-8 bg-zinc-800 border border-zinc-700 rounded-lg text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-lime-400/50 focus:ring-1 focus:ring-lime-400/25" />
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-zinc-500">s</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-zinc-400 mb-1">RPE / Intensity</label>
                                        <div class="relative">
                                            <input type="number" wire:model="targetRpe" placeholder="6" min="1" max="10" step="0.5"
                                                class="w-full px-3 py-2 pr-12 bg-zinc-800 border border-zinc-700 rounded-lg text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-lime-400/50 focus:ring-1 focus:ring-lime-400/25" />
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-zinc-500">1-10</span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Notes --}}
                        <div>
                            <textarea
                                wire:model="exerciseNotes"
                                placeholder="Add notes for this exercise..."
                                rows="2"
                                class="w-full px-3 py-2 bg-zinc-800 border border-zinc-700 rounded-lg text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-lime-400/50 focus:ring-1 focus:ring-lime-400/25 resize-none"
                            ></textarea>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="flex items-center justify-end gap-3 px-5 py-4 border-t border-zinc-800">
                        <button
                            wire:click="backToSearch"
                            class="px-4 py-2 text-sm text-zinc-400 hover:text-white transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            wire:click="addExercise"
                            class="flex items-center gap-1.5 px-5 py-2 bg-lime-400 text-zinc-900 rounded-lg text-sm font-semibold hover:bg-lime-300 transition-colors"
                        >
                            <flux:icon.plus class="size-4" />
                            Add Exercise
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
