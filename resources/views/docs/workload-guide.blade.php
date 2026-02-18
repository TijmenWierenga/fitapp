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
                            Workload tracking gives you an evidence-based view of your training by measuring three pillars:
                        </p>
                        <ul class="list-disc list-inside space-y-1">
                            <li><strong class="text-zinc-900 dark:text-white">Session Load (sRPE)</strong> — overall training stress per workout, combining duration and effort.</li>
                            <li><strong class="text-zinc-900 dark:text-white">Muscle Group Volume</strong> — weekly effective sets per muscle group from strength exercises.</li>
                            <li><strong class="text-zinc-900 dark:text-white">Strength Progression</strong> — estimated one-rep max trends for your lifts over time.</li>
                        </ul>
                        <p>
                            Together, these metrics help you balance training stress, spot muscle group imbalances, and track strength gains. Data is based on up to <strong class="text-zinc-900 dark:text-white">8 weeks (56 days)</strong> of completed workouts.
                        </p>
                        <p>
                            Only completed workouts with exercises linked to the exercise library are included. Unlinked exercises are counted but excluded from volume and progression calculations.
                        </p>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>

            <flux:accordion.item>
                <flux:accordion.heading>
                    Session Load (sRPE)
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="space-y-4 text-sm text-zinc-600 dark:text-zinc-400">
                        <p>
                            Session Load measures overall training stress for a single workout by combining how long you trained with how hard it felt. It uses the <strong class="text-zinc-900 dark:text-white">session RPE (sRPE)</strong> method.
                        </p>

                        <div class="px-3 py-2 bg-zinc-50 dark:bg-zinc-900 rounded-lg font-mono text-xs">
                            Session Load = Duration (minutes) &times; RPE
                        </div>

                        <div class="space-y-2">
                            <h4 class="font-medium text-zinc-900 dark:text-white">Example</h4>
                            <ul class="list-disc list-inside space-y-1">
                                <li>Monday: 60 min at RPE 7 = <strong class="text-zinc-900 dark:text-white">420</strong></li>
                                <li>Wednesday: 45 min at RPE 8 = <strong class="text-zinc-900 dark:text-white">360</strong></li>
                                <li>Weekly total = <strong class="text-zinc-900 dark:text-white">780</strong></li>
                            </ul>
                        </div>

                        <p>
                            The dashboard shows your <strong class="text-zinc-900 dark:text-white">weekly sRPE total</strong>, <strong class="text-zinc-900 dark:text-white">session count</strong>, and a <strong class="text-zinc-900 dark:text-white">4-week trend chart</strong>.
                        </p>
                        <p>
                            To be included, workouts need both a <strong class="text-zinc-900 dark:text-white">duration</strong> and an <strong class="text-zinc-900 dark:text-white">RPE</strong> when marked as complete.
                        </p>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>

            <flux:accordion.item>
                <flux:accordion.heading>
                    Monotony & Strain
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="space-y-4 text-sm text-zinc-600 dark:text-zinc-400">
                        <div class="space-y-3">
                            <h4 class="font-medium text-zinc-900 dark:text-white">Monotony</h4>
                            <p>
                                Monotony measures how varied your daily training load is across the week. Rest days count as zero load. A low monotony score means good variation between hard and easy days.
                            </p>

                            <div class="px-3 py-2 bg-zinc-50 dark:bg-zinc-900 rounded-lg font-mono text-xs">
                                Monotony = Average daily load / Standard deviation of daily loads
                            </div>

                            <p>
                                <strong class="text-zinc-900 dark:text-white">Warning:</strong> monotony above <strong class="text-zinc-900 dark:text-white">2.0</strong> means your training is too uniform. Vary your session intensity to bring it down.
                            </p>

                            <div class="space-y-2">
                                <h4 class="font-medium text-zinc-900 dark:text-white">Example</h4>
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Training 60 min at RPE 7 every single day → monotony is very high</li>
                                    <li>Mixing a 90 min RPE 8 session with a 30 min RPE 5 session and rest days → much lower monotony</li>
                                </ul>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <h4 class="font-medium text-zinc-900 dark:text-white">Strain</h4>
                            <p>
                                Strain combines your total weekly volume with monotony to flag overtraining risk. High strain means both high volume <em>and</em> high monotony.
                            </p>

                            <div class="px-3 py-2 bg-zinc-50 dark:bg-zinc-900 rounded-lg font-mono text-xs">
                                Strain = Weekly Total &times; Monotony
                            </div>
                        </div>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>

            <flux:accordion.item>
                <flux:accordion.heading>
                    Week-over-Week Change
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="space-y-4 text-sm text-zinc-600 dark:text-zinc-400">
                        <p>
                            This metric compares your current week's total sRPE to the previous week, expressed as a percentage change.
                        </p>

                        <div class="px-3 py-2 bg-zinc-50 dark:bg-zinc-900 rounded-lg font-mono text-xs">
                            Change % = ((Current Week - Previous Week) / Previous Week) &times; 100
                        </div>

                        <div class="space-y-2">
                            <h4 class="font-medium text-zinc-900 dark:text-white">Example</h4>
                            <p>Previous week total: 500. This week total: 600. Change: <strong class="text-zinc-900 dark:text-white">+20%</strong> → warning shown.</p>
                        </div>

                        <p>
                            <strong class="text-zinc-900 dark:text-white">Warning:</strong> a change exceeding <strong class="text-zinc-900 dark:text-white">&plusmn;15%</strong> triggers an alert. Aim for gradual progressions of <strong class="text-zinc-900 dark:text-white">10&ndash;15%</strong> per week.
                        </p>

                        <p>
                            When no previous week data exists (e.g., your first week of training), no comparison is shown.
                        </p>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>

            <flux:accordion.item>
                <flux:accordion.heading>
                    Muscle Group Volume
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="space-y-4 text-sm text-zinc-600 dark:text-zinc-400">
                        <p>
                            Muscle group volume tracks the number of <strong class="text-zinc-900 dark:text-white">effective weekly sets</strong> per muscle group, from strength exercises only.
                        </p>

                        <div class="space-y-3">
                            <h4 class="font-medium text-zinc-900 dark:text-white">How sets are counted</h4>
                            <p>
                                The exercise's <strong class="text-zinc-900 dark:text-white">target sets</strong> are used first. If not set, the block's <strong class="text-zinc-900 dark:text-white">round count</strong> is used. If neither is available, <strong class="text-zinc-900 dark:text-white">1 set</strong> is assumed.
                            </p>
                        </div>

                        <div class="space-y-3">
                            <h4 class="font-medium text-zinc-900 dark:text-white">Load factors</h4>
                            <p>
                                Exercises can target multiple muscle groups. Each muscle group receives a fraction of the exercise's sets based on its load factor:
                            </p>
                            <ul class="list-disc list-inside space-y-1">
                                <li><strong class="text-zinc-900 dark:text-white">Primary muscles (1.0)</strong> — receive the full number of sets.</li>
                                <li><strong class="text-zinc-900 dark:text-white">Secondary muscles (0.5)</strong> — receive half the sets.</li>
                            </ul>
                            <p>
                                <strong class="text-zinc-900 dark:text-white">Example:</strong> 3 sets of bench press → 3 sets for chest (primary, 1.0) + 1.5 sets for triceps (secondary, 0.5).
                            </p>
                        </div>

                        <div class="space-y-3">
                            <h4 class="font-medium text-zinc-900 dark:text-white">4-week average & trends</h4>
                            <p>The current week's volume is compared to the 4-week average:</p>
                            <ul class="list-disc list-inside space-y-1">
                                <li><strong class="text-zinc-900 dark:text-white">Increasing (↑)</strong> — current week exceeds average by more than 10%</li>
                                <li><strong class="text-zinc-900 dark:text-white">Stable (→)</strong> — within &plusmn;10% of the average</li>
                                <li><strong class="text-zinc-900 dark:text-white">Decreasing (↓)</strong> — current week is more than 10% below average</li>
                            </ul>
                        </div>

                        <p>
                            The dashboard shows horizontal bars with a 4-week average marker, trend arrows, and injury badges for affected body parts.
                        </p>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>

            <flux:accordion.item>
                <flux:accordion.heading>
                    Strength Progression (Estimated 1RM)
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="space-y-4 text-sm text-zinc-600 dark:text-zinc-400">
                        <p>
                            Strength progression tracks your <strong class="text-zinc-900 dark:text-white">estimated one-rep max (e1RM)</strong> for strength exercises over time, using the Epley formula.
                        </p>

                        <div class="px-3 py-2 bg-zinc-50 dark:bg-zinc-900 rounded-lg font-mono text-xs">
                            e1RM = Weight &times; (1 + Reps / 30)
                        </div>

                        <p>
                            For single-rep lifts, the actual weight lifted is used directly.
                        </p>

                        <div class="space-y-2">
                            <h4 class="font-medium text-zinc-900 dark:text-white">How it works</h4>
                            <p>
                                Your best e1RM from the <strong class="text-zinc-900 dark:text-white">last 4 weeks</strong> is compared to the best e1RM from the <strong class="text-zinc-900 dark:text-white">4 weeks before that</strong>. The change is shown as a percentage.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <h4 class="font-medium text-zinc-900 dark:text-white">Example</h4>
                            <p>Bench press: 80 kg &times; 8 reps → e1RM &asymp; 101.3 kg. Previous best was 96 kg → <strong class="text-zinc-900 dark:text-white">+5.5%</strong> progression.</p>
                        </div>

                        <p>
                            The dashboard shows a table with each exercise's current e1RM, previous e1RM, and change percentage. <strong class="text-green-600 dark:text-green-400">Green</strong> indicates progression, <strong class="text-red-600 dark:text-red-400">red</strong> indicates regression.
                        </p>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>

            <flux:accordion.item>
                <flux:accordion.heading>
                    Warnings & Alerts
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                        <p>The dashboard highlights potential issues with your training:</p>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                        <th class="py-2 pr-4 font-medium text-zinc-900 dark:text-white">Warning</th>
                                        <th class="py-2 pr-4 font-medium text-zinc-900 dark:text-white">Trigger</th>
                                        <th class="py-2 font-medium text-zinc-900 dark:text-white">What to do</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    <tr>
                                        <td class="py-2 pr-4">Rapid load change</td>
                                        <td class="py-2 pr-4">Week-over-week change exceeds &plusmn;15%</td>
                                        <td class="py-2">Moderate increases to 10&ndash;15% per week</td>
                                    </tr>
                                    <tr>
                                        <td class="py-2 pr-4">High monotony</td>
                                        <td class="py-2 pr-4">Monotony above 2.0</td>
                                        <td class="py-2">Vary session intensity &mdash; mix hard and easy days</td>
                                    </tr>
                                    <tr>
                                        <td class="py-2 pr-4">Unlinked exercises</td>
                                        <td class="py-2 pr-4">Exercises not linked to the library</td>
                                        <td class="py-2">Link them via the AI assistant for accurate tracking</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
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
                            Workload tracking relies on the exercise library to know which muscle groups an exercise targets and to calculate strength progression. When a workout contains exercises that aren't linked to a library entry, they can't be assigned to any muscle group or included in e1RM calculations.
                        </p>
                        <p>
                            The dashboard shows the number of unlinked exercises when applicable. To include them in your volume and progression data, link them to the exercise library through your AI assistant.
                        </p>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>
        </flux:accordion>
    </div>
</x-layouts.app>
