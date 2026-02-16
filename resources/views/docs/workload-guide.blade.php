<x-layouts.app :title="__('Workload Guide')">
    <div class="max-w-3xl mx-auto">
        <div class="mb-8">
            <flux:heading size="xl">Workload Guide</flux:heading>
            <flux:text class="mt-2">
                Understand how {{ config('app.name') }} tracks your training load and helps you train smarter.
            </flux:text>
        </div>

        <flux:accordion>
            <flux:accordion.item :expanded="true">
                <flux:accordion.heading>
                    What is workload tracking?
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                        <p>
                            Workload tracking measures how much training stress you're placing on each muscle group over time. By comparing your recent training to your longer-term average, it helps you spot when you're doing too much (injury risk) or too little (detraining).
                        </p>
                        <p>
                            The dashboard widget shows your <strong class="text-zinc-900 dark:text-white">acute load</strong> (last 7 days) for each muscle group and uses the <strong class="text-zinc-900 dark:text-white">Acute:Chronic Workload Ratio (ACWR)</strong> to colour-code your training zones.
                        </p>
                        <p>
                            Only completed workouts with exercises linked to the exercise library are included. Unlinked exercises are counted but excluded from load calculations.
                        </p>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>

            <flux:accordion.item>
                <flux:accordion.heading>
                    How is training volume calculated?
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="space-y-4 text-sm text-zinc-600 dark:text-zinc-400">
                        <p>Volume is calculated differently for each exercise type:</p>

                        <div class="space-y-3">
                            <div>
                                <h4 class="font-medium text-zinc-900 dark:text-white mb-1">Strength exercises</h4>
                                <div class="px-3 py-2 bg-zinc-50 dark:bg-zinc-900 rounded-lg font-mono text-xs">
                                    volume = sets &times; reps &times; (RPE / 10)
                                </div>
                                <p class="mt-1">Sets default to the block's round count if not set. RPE defaults to 5 when not specified.</p>
                            </div>

                            <div>
                                <h4 class="font-medium text-zinc-900 dark:text-white mb-1">Cardio exercises</h4>
                                <div class="px-3 py-2 bg-zinc-50 dark:bg-zinc-900 rounded-lg font-mono text-xs">
                                    volume = (duration in min / 10) &times; (HR zone / 5)
                                </div>
                                <p class="mt-1">Heart rate zone defaults to 3 when not specified.</p>
                            </div>

                            <div>
                                <h4 class="font-medium text-zinc-900 dark:text-white mb-1">Duration exercises</h4>
                                <div class="px-3 py-2 bg-zinc-50 dark:bg-zinc-900 rounded-lg font-mono text-xs">
                                    volume = duration in min &times; (RPE / 10)
                                </div>
                                <p class="mt-1">RPE defaults to 5 when not specified.</p>
                            </div>
                        </div>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>

            <flux:accordion.item>
                <flux:accordion.heading>
                    What is ACWR?
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                        <p>
                            The <strong class="text-zinc-900 dark:text-white">Acute:Chronic Workload Ratio (ACWR)</strong> compares your short-term training load to your longer-term average.
                        </p>

                        <ul class="list-disc list-inside space-y-1">
                            <li><strong class="text-zinc-900 dark:text-white">Acute load</strong> — total volume for a muscle group in the last <strong class="text-zinc-900 dark:text-white">7 days</strong>.</li>
                            <li><strong class="text-zinc-900 dark:text-white">Chronic load</strong> — average weekly volume over the last <strong class="text-zinc-900 dark:text-white">28 days</strong> (4 weeks).</li>
                        </ul>

                        <div class="px-3 py-2 bg-zinc-50 dark:bg-zinc-900 rounded-lg font-mono text-xs">
                            ACWR = acute load / chronic load
                        </div>

                        <p>
                            An ACWR of 1.0 means your recent week matches your average. Values above 1.0 mean you're training harder than usual; values below 1.0 mean you're training less.
                        </p>
                        <p>
                            When there is no chronic load data (e.g., your first week of training), the ACWR is displayed as 0 and the zone is shown as inactive.
                        </p>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>

            <flux:accordion.item>
                <flux:accordion.heading>
                    Training zones
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                        <p>Each muscle group is placed into a training zone based on its ACWR value:</p>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                        <th class="py-2 pr-4 font-medium text-zinc-900 dark:text-white">Zone</th>
                                        <th class="py-2 pr-4 font-medium text-zinc-900 dark:text-white">ACWR</th>
                                        <th class="py-2 font-medium text-zinc-900 dark:text-white">Guidance</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    <tr>
                                        <td class="py-2 pr-4">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-block w-2 h-2 rounded-full bg-zinc-400"></span> Undertraining
                                            </div>
                                        </td>
                                        <td class="py-2 pr-4">Below 0.8</td>
                                        <td class="py-2">You may be losing fitness. Gradually increase your training volume.</td>
                                    </tr>
                                    <tr>
                                        <td class="py-2 pr-4">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-block w-2 h-2 rounded-full bg-green-500"></span> Sweet Spot
                                            </div>
                                        </td>
                                        <td class="py-2 pr-4">0.8 – 1.3</td>
                                        <td class="py-2">Optimal training zone. You're progressing safely.</td>
                                    </tr>
                                    <tr>
                                        <td class="py-2 pr-4">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-block w-2 h-2 rounded-full bg-yellow-500"></span> Caution
                                            </div>
                                        </td>
                                        <td class="py-2 pr-4">1.3 – 1.5</td>
                                        <td class="py-2">Elevated injury risk. Monitor recovery closely and avoid further spikes.</td>
                                    </tr>
                                    <tr>
                                        <td class="py-2 pr-4">
                                            <div class="flex items-center gap-2">
                                                <span class="inline-block w-2 h-2 rounded-full bg-red-500"></span> Danger
                                            </div>
                                        </td>
                                        <td class="py-2 pr-4">Above 1.5</td>
                                        <td class="py-2">High injury risk. Consider reducing load or adding rest days.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>

            <flux:accordion.item>
                <flux:accordion.heading>
                    Muscle group load factors
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                        <p>
                            Exercises can target multiple muscle groups. Each muscle group is assigned a <strong class="text-zinc-900 dark:text-white">load factor</strong> that determines how much of the exercise volume counts toward that muscle group.
                        </p>
                        <ul class="list-disc list-inside space-y-1">
                            <li><strong class="text-zinc-900 dark:text-white">Primary muscles (1.0)</strong> — receive the full training volume. For example, chest on a bench press.</li>
                            <li><strong class="text-zinc-900 dark:text-white">Secondary muscles (0.5)</strong> — receive half the training volume. For example, triceps on a bench press.</li>
                        </ul>
                        <p>
                            Load factors are defined per exercise in the exercise library. This means a single exercise can contribute different amounts of load to different muscle groups.
                        </p>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>

            <flux:accordion.item>
                <flux:accordion.heading>
                    Why are some exercises excluded?
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                        <p>
                            Workload tracking relies on the exercise library to know which muscle groups an exercise targets. When a workout contains exercises that aren't linked to a library entry, they can't be assigned to any muscle group.
                        </p>
                        <p>
                            The number of unlinked exercises is shown at the bottom of the workload widget. To include them in your workload data, link them to the exercise library through your AI assistant.
                        </p>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>
        </flux:accordion>
    </div>
</x-layouts.app>
