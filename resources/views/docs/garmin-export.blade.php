<x-layouts.app :title="__('Garmin Export Guide')">
    <div class="max-w-3xl mx-auto">
        <div class="mb-8">
            <div class="mb-4">
                <a href="{{ route('docs.index') }}" class="inline-flex items-center gap-1 text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200" wire:navigate>
                    <flux:icon.arrow-left class="size-4" />
                    Documentation
                </a>
            </div>
            <flux:heading size="xl">Garmin Export Guide</flux:heading>
            <flux:text class="mt-2">
                Export your workouts as FIT files and transfer them to your Garmin watch.
            </flux:text>
        </div>

        <flux:accordion>
            <flux:accordion.item :expanded="true">
                <flux:accordion.heading>
                    What is a FIT file?
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                        <p>
                            <strong class="text-zinc-900 dark:text-white">FIT (Flexible and Interoperable Data Transfer)</strong> is the standard file format used by Garmin devices for workouts, activities, and courses. It's a compact binary format designed specifically for fitness data.
                        </p>
                        <p>
                            {{ config('app.name') }} exports your workout structure — including sections, exercises, intervals, rest periods, and targets — into a FIT file that your Garmin watch can read and execute as a structured workout.
                        </p>
                        <p>
                            Supported activities include running, cycling, swimming, strength training, HIIT, yoga, pilates, and more.
                        </p>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>

            <flux:accordion.item>
                <flux:accordion.heading>
                    How to export a FIT file
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                        <p>
                            Open any workout and click <strong class="text-zinc-900 dark:text-white">Export to Garmin</strong> in the actions panel. Your browser will download a <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs dark:bg-zinc-800">.fit</code> file, for example <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs dark:bg-zinc-800">2026-03-15-morning-run.fit</code>.
                        </p>
                        <p>
                            This works for both planned and completed workouts.
                        </p>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>

            <flux:accordion.item>
                <flux:accordion.heading>
                    Garmin exercise animations
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                        <p>
                            Exercises that have Garmin mappings in the exercise library will display <strong class="text-zinc-900 dark:text-white">animations on your watch</strong>, showing you the correct form for each movement.
                        </p>
                        <p>
                            Exercises without Garmin mappings still appear as named steps on the watch — they just won't show animations.
                        </p>
                        <p>
                            To get the best experience, enable <strong class="text-zinc-900 dark:text-white">Prefer Garmin exercises</strong> in your <a href="{{ route('fitness-profile.edit') }}" class="underline hover:text-zinc-900 dark:hover:text-white" wire:navigate>fitness profile settings</a>. This tells the AI assistant to prioritise exercises with Garmin mappings when creating workouts for you.
                        </p>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>

            <flux:accordion.item>
                <flux:accordion.heading>
                    Transferring to your Garmin watch
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="space-y-6 text-sm text-zinc-600 dark:text-zinc-400">
                        <div>
                            <h4 class="mb-2 font-medium text-zinc-900 dark:text-white">Windows</h4>
                            <ol class="list-decimal list-inside space-y-1.5">
                                <li>Connect your watch to your computer via USB cable.</li>
                                <li>Open <strong class="text-zinc-900 dark:text-white">File Explorer</strong> — the watch appears as a removable drive.</li>
                                <li>Navigate to <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs dark:bg-zinc-800">GARMIN\NewFiles</code>.</li>
                                <li>Drag and drop the <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs dark:bg-zinc-800">.fit</code> file into that folder.</li>
                                <li>Safely eject the watch and disconnect. The workout will appear on your watch.</li>
                            </ol>
                        </div>

                        <div>
                            <h4 class="mb-2 font-medium text-zinc-900 dark:text-white">Mac</h4>
                            <p class="mb-2">
                                macOS doesn't natively support MTP (the protocol Garmin watches use for file transfer), so you need a free tool called <a href="https://openmtp.ganeshrvel.com/" target="_blank" rel="noopener" class="underline hover:text-zinc-900 dark:hover:text-white">OpenMTP</a>.
                            </p>
                            <ol class="list-decimal list-inside space-y-1.5">
                                <li>Install <a href="https://openmtp.ganeshrvel.com/" target="_blank" rel="noopener" class="underline hover:text-zinc-900 dark:hover:text-white">OpenMTP</a> (free, open-source).</li>
                                <li>Connect your watch to your computer via USB cable.</li>
                                <li>Open OpenMTP — the watch appears as a connected device.</li>
                                <li>Navigate to <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs dark:bg-zinc-800">GARMIN/NewFiles</code>.</li>
                                <li>Drag and drop the <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs dark:bg-zinc-800">.fit</code> file into that folder.</li>
                                <li>Disconnect. The workout will appear on your watch.</li>
                            </ol>
                        </div>

                        <div>
                            <h4 class="mb-2 font-medium text-zinc-900 dark:text-white">Android</h4>
                            <p class="mb-2">
                                You can transfer FIT files directly from your Android phone using a <strong class="text-zinc-900 dark:text-white">USB OTG (On-The-Go)</strong> adapter to connect your Garmin watch to your phone.
                            </p>
                            <ol class="list-decimal list-inside space-y-1.5">
                                <li>Connect your Garmin watch to your phone using a USB OTG adapter (e.g. USB-C to USB-C or USB-C to Micro-USB, depending on your devices).</li>
                                <li>Open a file manager app (most Android phones include one by default).</li>
                                <li>The watch should appear as an external storage device.</li>
                                <li>Navigate to <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs dark:bg-zinc-800">GARMIN/NewFiles</code> on the watch.</li>
                                <li>Copy the downloaded <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs dark:bg-zinc-800">.fit</code> file into that folder.</li>
                                <li>Disconnect the watch. The workout will appear on your watch.</li>
                            </ol>
                        </div>

                        <div>
                            <h4 class="mb-2 font-medium text-zinc-900 dark:text-white">iPhone</h4>
                            <p>
                                iOS does not support direct file transfer to Garmin watches via USB. To get the FIT file onto your watch, transfer it to a computer first — for example via <strong class="text-zinc-900 dark:text-white">AirDrop</strong>, <strong class="text-zinc-900 dark:text-white">iCloud Drive</strong>, or <strong class="text-zinc-900 dark:text-white">email</strong> — and then follow the Windows or Mac instructions above.
                            </p>
                        </div>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>

            <flux:accordion.item>
                <flux:accordion.heading>
                    What gets exported?
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                        <p>The FIT export includes the full workout structure:</p>
                        <ul class="list-disc list-inside space-y-1.5">
                            <li><strong class="text-zinc-900 dark:text-white">Workout name and sport type</strong></li>
                            <li><strong class="text-zinc-900 dark:text-white">Warmup/cooldown detection</strong> — sections with names containing "warm" or "cool" are mapped to the corresponding Garmin step types</li>
                            <li><strong class="text-zinc-900 dark:text-white">Exercise steps</strong> with timing, distance, or open targets</li>
                            <li><strong class="text-zinc-900 dark:text-white">Rest periods</strong> and repeat structures (circuits, supersets, intervals, EMOM, AMRAP)</li>
                            <li><strong class="text-zinc-900 dark:text-white">Heart rate zones</strong>, pace ranges, and power targets</li>
                            <li><strong class="text-zinc-900 dark:text-white">Strength details in notes</strong> — sets, reps, weight, RPE, tempo, and rest</li>
                        </ul>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>

            <flux:accordion.item>
                <flux:accordion.heading>
                    Common pitfalls
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="space-y-4 text-sm text-zinc-600 dark:text-zinc-400">
                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white">File in wrong folder</h4>
                            <p>The file must be placed in <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs dark:bg-zinc-800">GARMIN/NewFiles</code> (or <code class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs dark:bg-zinc-800">GARMIN\NewFiles</code> on Windows), not the root or any other subfolder.</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white">Watch not recognized (Windows)</h4>
                            <p>Try a different USB port or cable. Make sure the watch is not in charging-only mode.</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white">Watch not recognized (Mac)</h4>
                            <p>macOS needs OpenMTP or a similar tool. Android File Transfer is known to be unreliable with Garmin devices on modern macOS.</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white">OpenMTP doesn't see the watch</h4>
                            <p>Try a different USB cable — some cables are charge-only and don't support data transfer.</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white">Watch not recognized (Android OTG)</h4>
                            <p>Not all phones support USB OTG — check your device specifications. Also make sure you're using a data-capable OTG cable or adapter, not a charge-only cable.</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white">Workout doesn't appear after transfer</h4>
                            <p>Disconnect the watch and wait a moment. Some watches need a few seconds to process new files. If it still doesn't appear, restart the watch.</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white">No exercise animations</h4>
                            <p>Exercises need Garmin mappings in the exercise library. Use the AI assistant to link them, or enable "Prefer Garmin exercises" in your fitness profile.</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white">Section shows "Active" instead of "Warmup"</h4>
                            <p>The section name must contain "warm" or "cool" to be detected as a warmup or cooldown step on the Garmin device.</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white">Too many workouts on the watch</h4>
                            <p>Garmin watches have a limit on stored workouts. Delete old ones from the watch if you can't add new files.</p>
                        </div>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>
        </flux:accordion>
    </div>
</x-layouts.app>
