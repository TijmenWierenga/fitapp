<div>
    @if($workout)
        <flux:modal name="import-fit" wire:model.live="showModal" @close="closeModal" class="max-w-2xl">
            {{-- Upload step --}}
            @if($step === 'upload')
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Import Garmin Data</flux:heading>
                        <flux:text class="mt-1">Upload a .FIT file to import activity data into <strong>{{ $workout->name }}</strong>.</flux:text>
                    </div>

                    <flux:file-upload wire:model="fitFile" label="Upload .FIT file" accept=".fit">
                        <flux:file-upload.dropzone
                            heading="Drop your .FIT file here or click to browse"
                            text="Garmin activity files (.FIT) up to 10MB"
                        />
                    </flux:file-upload>

                    @if($fitFile && !$parseError)
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

                    <div class="flex gap-2 justify-end">
                        <flux:button type="button" wire:click="closeModal" variant="ghost">Cancel</flux:button>
                    </div>
                </div>
            @endif

            {{-- Preview step --}}
            @if($step === 'preview' && $preview)
                <div class="space-y-6">
                    <div>
                        <flux:heading size="lg">Activity Preview</flux:heading>
                        <flux:text class="mt-1">Review the imported data before applying it to <strong>{{ $workout->name }}</strong>.</flux:text>
                    </div>

                    {{-- Activity summary grid --}}
                    <div class="grid grid-cols-2 gap-3">
                        @if($preview['duration'])
                            <div class="rounded-lg bg-zinc-100 dark:bg-zinc-800 px-3 py-2">
                                <div class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 uppercase">Duration</div>
                                <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $preview['duration'] }}</div>
                            </div>
                        @endif

                        @if($preview['calories'])
                            <div class="rounded-lg bg-zinc-100 dark:bg-zinc-800 px-3 py-2">
                                <div class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 uppercase">Calories</div>
                                <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $preview['calories'] }} kcal</div>
                            </div>
                        @endif

                        @if($preview['distance'])
                            <div class="rounded-lg bg-zinc-100 dark:bg-zinc-800 px-3 py-2">
                                <div class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 uppercase">Distance</div>
                                <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $preview['distance'] }}</div>
                            </div>
                        @endif

                        @if($preview['avgHeartRate'])
                            <div class="rounded-lg bg-zinc-100 dark:bg-zinc-800 px-3 py-2">
                                <div class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 uppercase">Avg HR</div>
                                <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $preview['avgHeartRate'] }} bpm</div>
                            </div>
                        @endif

                        @if($preview['maxHeartRate'])
                            <div class="rounded-lg bg-zinc-100 dark:bg-zinc-800 px-3 py-2">
                                <div class="text-[10px] font-medium text-zinc-400 dark:text-zinc-500 uppercase">Max HR</div>
                                <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $preview['maxHeartRate'] }} bpm</div>
                            </div>
                        @endif
                    </div>

                    {{-- Duplicate warning --}}
                    @if($duplicateWarning)
                        <flux:callout variant="warning">
                            <flux:callout.heading>Possible duplicate</flux:callout.heading>
                            <flux:callout.text>{{ $duplicateWarning }}</flux:callout.text>
                        </flux:callout>
                    @endif

                    {{-- Mismatch warning --}}
                    @if($mismatchWarning)
                        <flux:callout variant="warning">
                            <flux:callout.heading>Exercise count mismatch</flux:callout.heading>
                            <flux:callout.text>{{ $mismatchWarning }}</flux:callout.text>
                        </flux:callout>
                    @endif

                    {{-- RPE Section --}}
                    <flux:field>
                        <flux:label>Rate of Perceived Exertion (RPE)</flux:label>
                        <flux:description>How hard did this workout feel? (optional)</flux:description>
                        <div class="mt-3">
                            <x-numeric-scale :min="1" :max="10" wire="rpe" :selected="$rpe" />
                            <div class="flex justify-between mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                <span>Very Easy</span>
                                <span>Easy</span>
                                <span>Moderate</span>
                                <span>Hard</span>
                                <span>Maximum</span>
                            </div>
                        </div>
                        <flux:error name="rpe" />
                    </flux:field>

                    {{-- Feeling Section --}}
                    <flux:field>
                        <flux:label>Overall Feeling</flux:label>
                        <flux:description>How did you feel during this workout? (optional)</flux:description>
                        <div class="mt-3">
                            @php($feelingScale = \App\Models\Workout::feelingScale())
                            <div class="flex justify-between gap-2">
                                @foreach($feelingScale as $value => $data)
                                    <button
                                        type="button"
                                        wire:click="$set('feeling', {{ $value }})"
                                        class="flex-1 py-3 text-3xl rounded-md transition-colors {{ $feeling === $value ? 'bg-accent' : 'bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600' }}"
                                    >
                                        {{ $data['emoji'] }}
                                    </button>
                                @endforeach
                            </div>
                            <div class="flex justify-between mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                                @foreach($feelingScale as $data)
                                    <span class="flex-1 text-center">{{ $data['label'] }}</span>
                                @endforeach
                            </div>
                        </div>
                        <flux:error name="feeling" />
                    </flux:field>

                    <div class="flex gap-2 justify-between">
                        <flux:button type="button" wire:click="closeModal" variant="ghost">Cancel</flux:button>
                        <flux:button wire:click="confirmImport" variant="primary" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="confirmImport">Import</span>
                            <span wire:loading wire:target="confirmImport">Importing...</span>
                        </flux:button>
                    </div>
                </div>
            @endif
        </flux:modal>
    @endif
</div>
