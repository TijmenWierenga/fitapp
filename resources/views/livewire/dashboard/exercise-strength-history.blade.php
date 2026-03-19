<div>
    <flux:modal wire:model.self="showModal" class="md:w-[520px]">
        @if($this->historyResult)
            <flux:heading size="lg">Strength History</flux:heading>

            <div class="mt-4">
                <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $this->historyResult->exerciseName }}</div>

                <flux:radio.group wire:model.live="range" variant="segmented" size="sm" class="mt-3">
                    @foreach(\App\Domain\Workload\Enums\HistoryRange::cases() as $case)
                        <flux:radio :value="$case->value" :label="$case->label()" />
                    @endforeach
                </flux:radio.group>
            </div>

            @if(empty($this->chartData))
                <div class="mt-6">
                    <flux:text class="text-zinc-500 dark:text-zinc-400">
                        No strength data for this exercise in the selected time range.
                    </flux:text>
                </div>
            @else
                @php
                    $points = $this->historyResult->points;
                    $lastPoint = end($points);
                    $firstPoint = reset($points);

                    $maxWeightChange = count($points) > 1 ? $lastPoint->maxWeight - $firstPoint->maxWeight : null;
                    $volumeChangePct = count($points) > 1 && $firstPoint->volume > 0
                        ? (($lastPoint->volume - $firstPoint->volume) / $firstPoint->volume) * 100
                        : null;
                    $e1rmChangePct = count($points) > 1 && $firstPoint->estimated1RM > 0
                        ? (($lastPoint->estimated1RM - $firstPoint->estimated1RM) / $firstPoint->estimated1RM) * 100
                        : null;
                @endphp

                <div class="mt-6 space-y-6">
                    {{-- Max Weight Chart --}}
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <div class="text-[10px] font-medium uppercase tracking-widest text-zinc-500 dark:text-zinc-400">Max Weight</div>
                                <div class="text-xl font-bold tabular-nums text-zinc-900 dark:text-zinc-100">{{ number_format($lastPoint->maxWeight, 1) }} kg</div>
                            </div>
                            @if($maxWeightChange !== null)
                                <div @class([
                                    'text-xs font-semibold px-2 py-1 rounded-md',
                                    'bg-green-500/10 text-green-600 dark:text-green-400' => $maxWeightChange >= 0,
                                    'bg-red-500/10 text-red-600 dark:text-red-400' => $maxWeightChange < 0,
                                ])>
                                    {{ $maxWeightChange >= 0 ? '+' : '' }}{{ number_format($maxWeightChange, 1) }} kg
                                </div>
                            @endif
                        </div>
                        <flux:chart :value="$this->chartData" class="h-[120px]">
                            <flux:chart.svg>
                                <flux:chart.line field="maxWeight" class="text-accent" />
                                <flux:chart.area field="maxWeight" class="text-accent/10" />
                                <flux:chart.point field="maxWeight" class="text-accent" />
                                <flux:chart.axis axis="x" field="date">
                                    <flux:chart.axis.tick />
                                    <flux:chart.axis.line />
                                </flux:chart.axis>
                                <flux:chart.axis axis="y">
                                    <flux:chart.axis.grid />
                                </flux:chart.axis>
                                <flux:chart.cursor />
                            </flux:chart.svg>
                            <flux:chart.tooltip>
                                <flux:chart.tooltip.heading field="date" />
                                <flux:chart.tooltip.value field="maxWeight" label="Weight" suffix=" kg" />
                            </flux:chart.tooltip>
                        </flux:chart>
                    </div>

                    {{-- Volume Chart --}}
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <div class="text-[10px] font-medium uppercase tracking-widest text-zinc-500 dark:text-zinc-400">Volume (Tonnage)</div>
                                <div class="text-xl font-bold tabular-nums text-zinc-900 dark:text-zinc-100">{{ number_format($lastPoint->volume, 0) }} kg</div>
                            </div>
                            @if($volumeChangePct !== null)
                                <div @class([
                                    'text-xs font-semibold px-2 py-1 rounded-md',
                                    'bg-green-500/10 text-green-600 dark:text-green-400' => $volumeChangePct >= 0,
                                    'bg-red-500/10 text-red-600 dark:text-red-400' => $volumeChangePct < 0,
                                ])>
                                    {{ $volumeChangePct >= 0 ? '+' : '' }}{{ number_format($volumeChangePct, 1) }}%
                                </div>
                            @endif
                        </div>
                        <flux:chart :value="$this->chartData" class="h-[120px]">
                            <flux:chart.svg>
                                <flux:chart.line field="volume" class="text-accent" />
                                <flux:chart.area field="volume" class="text-accent/10" />
                                <flux:chart.point field="volume" class="text-accent" />
                                <flux:chart.axis axis="x" field="date">
                                    <flux:chart.axis.tick />
                                    <flux:chart.axis.line />
                                </flux:chart.axis>
                                <flux:chart.axis axis="y">
                                    <flux:chart.axis.grid />
                                </flux:chart.axis>
                                <flux:chart.cursor />
                            </flux:chart.svg>
                            <flux:chart.tooltip>
                                <flux:chart.tooltip.heading field="date" />
                                <flux:chart.tooltip.value field="volume" label="Volume" suffix=" kg" />
                            </flux:chart.tooltip>
                        </flux:chart>
                    </div>

                    {{-- Estimated 1RM Chart --}}
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <div class="text-[10px] font-medium uppercase tracking-widest text-zinc-500 dark:text-zinc-400">Estimated 1RM</div>
                                <div class="text-xl font-bold tabular-nums text-zinc-900 dark:text-zinc-100">{{ number_format($lastPoint->estimated1RM, 1) }} kg</div>
                            </div>
                            @if($e1rmChangePct !== null)
                                <div @class([
                                    'text-xs font-semibold px-2 py-1 rounded-md',
                                    'bg-green-500/10 text-green-600 dark:text-green-400' => $e1rmChangePct >= 0,
                                    'bg-red-500/10 text-red-600 dark:text-red-400' => $e1rmChangePct < 0,
                                ])>
                                    {{ $e1rmChangePct >= 0 ? '+' : '' }}{{ number_format($e1rmChangePct, 1) }}%
                                </div>
                            @endif
                        </div>
                        <flux:chart :value="$this->chartData" class="h-[120px]">
                            <flux:chart.svg>
                                <flux:chart.line field="e1rm" class="text-accent" />
                                <flux:chart.area field="e1rm" class="text-accent/10" />
                                <flux:chart.point field="e1rm" class="text-accent" />
                                <flux:chart.axis axis="x" field="date">
                                    <flux:chart.axis.tick />
                                    <flux:chart.axis.line />
                                </flux:chart.axis>
                                <flux:chart.axis axis="y">
                                    <flux:chart.axis.grid />
                                </flux:chart.axis>
                                <flux:chart.cursor />
                            </flux:chart.svg>
                            <flux:chart.tooltip>
                                <flux:chart.tooltip.heading field="date" />
                                <flux:chart.tooltip.value field="e1rm" label="e1RM" suffix=" kg" />
                            </flux:chart.tooltip>
                        </flux:chart>
                    </div>
                </div>
            @endif
        @endif
    </flux:modal>
</div>
