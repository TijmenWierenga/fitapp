<div>
    {{-- Hero Section --}}
    <div class="text-center mb-12">
        <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-white mb-4">
            Get Started with {{ config('app.name') }}
        </h1>
        <p class="text-lg text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto">
            Connect your AI assistant to {{ config('app.name') }} in just a few steps. Your personal AI coach will be ready to create and manage your workouts.
        </p>

        {{-- Progress Pills --}}
        <div class="flex items-center justify-center gap-3 mt-8">
            @for ($i = 1; $i <= 3; $i++)
                <button
                    wire:click="goToStep({{ $i }})"
                    class="flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition-all
                        {{ $currentStep === $i
                            ? 'bg-brand-lime text-black'
                            : ($currentStep > $i
                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400') }}"
                >
                    @if ($currentStep > $i)
                        <flux:icon.check variant="mini" class="w-4 h-4" />
                    @else
                        <span class="w-5 h-5 flex items-center justify-center rounded-full bg-white/20 text-xs">{{ $i }}</span>
                    @endif
                    <span>
                        @switch($i)
                            @case(1) Choose Method @break
                            @case(2) Configure Claude @break
                            @case(3) Start Training @break
                        @endswitch
                    </span>
                </button>
            @endfor
        </div>
    </div>

    {{-- Step Content --}}
    <div class="space-y-6">
        {{-- Step 1: Choose Setup Method --}}
        <flux:accordion exclusive wire:key="accordion-{{ $currentStep }}">
            <flux:accordion.item :expanded="$currentStep === 1">
                <flux:accordion.heading>
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-8 h-8 rounded-full {{ $currentStep > 1 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-brand-lime text-black' }} text-sm font-bold">
                            @if ($currentStep > 1)
                                <flux:icon.check variant="mini" class="w-5 h-5" />
                            @else
                                1
                            @endif
                        </span>
                        <span class="font-semibold text-zinc-900 dark:text-white">Choose Your Setup Method</span>
                    </div>
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="pl-11 space-y-6">
                        <p class="text-zinc-600 dark:text-zinc-400">
                            Select how you'd like to connect Claude to {{ config('app.name') }}. Both options use secure OAuth authentication.
                        </p>

                        <div class="grid md:grid-cols-2 gap-4">
                            {{-- Claude Desktop Option --}}
                            <button
                                wire:click="selectMethod('desktop')"
                                class="relative p-6 rounded-xl border-2 text-left transition-all hover:border-brand-lime hover:bg-zinc-50 dark:hover:bg-zinc-900
                                    {{ $setupMethod === 'desktop' ? 'border-brand-lime bg-zinc-50 dark:bg-zinc-900' : 'border-zinc-200 dark:border-zinc-700' }}"
                            >
                                <span class="absolute top-3 right-3 px-2 py-0.5 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-full">
                                    Recommended
                                </span>
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon.computer-desktop class="w-6 h-6 text-zinc-600 dark:text-zinc-400" />
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-zinc-900 dark:text-white mb-1">Claude Desktop</h3>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                            Add as MCP server in Claude Desktop settings
                                        </p>
                                    </div>
                                </div>
                            </button>

                            {{-- Claude CLI Option --}}
                            <button
                                wire:click="selectMethod('cli')"
                                class="p-6 rounded-xl border-2 text-left transition-all hover:border-brand-lime hover:bg-zinc-50 dark:hover:bg-zinc-900
                                    {{ $setupMethod === 'cli' ? 'border-brand-lime bg-zinc-50 dark:bg-zinc-900' : 'border-zinc-200 dark:border-zinc-700' }}"
                            >
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon.command-line class="w-6 h-6 text-zinc-600 dark:text-zinc-400" />
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-zinc-900 dark:text-white mb-1">Claude Code CLI</h3>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                            Run a single command in your terminal
                                        </p>
                                    </div>
                                </div>
                            </button>
                        </div>

                        <flux:button wire:click="goToStep(2)" variant="primary">
                            Continue with {{ $setupMethod === 'desktop' ? 'Claude Desktop' : 'Claude CLI' }}
                        </flux:button>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>

            {{-- Step 2: Configure Claude --}}
            <flux:accordion.item :expanded="$currentStep === 2">
                <flux:accordion.heading>
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-8 h-8 rounded-full {{ $currentStep > 2 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : ($currentStep === 2 ? 'bg-brand-lime text-black' : 'bg-zinc-200 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400') }} text-sm font-bold">
                            @if ($currentStep > 2)
                                <flux:icon.check variant="mini" class="w-5 h-5" />
                            @else
                                2
                            @endif
                        </span>
                        <span class="font-semibold text-zinc-900 dark:text-white">Configure Claude</span>
                    </div>
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="pl-11 space-y-6">
                        @if ($setupMethod === 'desktop')
                            {{-- Claude Desktop Instructions --}}
                            <p class="text-zinc-600 dark:text-zinc-400">
                                Follow these steps to add {{ config('app.name') }} as an MCP server in Claude Desktop:
                            </p>

                            <div class="space-y-4">
                                <div class="flex gap-4">
                                    <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-sm font-medium text-zinc-600 dark:text-zinc-400">1</span>
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">Open Claude Desktop Settings</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Click on your profile icon and select "Settings"</p>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-sm font-medium text-zinc-600 dark:text-zinc-400">2</span>
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">Navigate to Connectors</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Find "Connectors" in the left sidebar</p>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-sm font-medium text-zinc-600 dark:text-zinc-400">3</span>
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">Add a Custom Connector</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Click "Add Custom Connector" and enter the URL below:</p>
                                    </div>
                                </div>
                            </div>

                            <div
                                x-data="{
                                    copied: false,
                                    copy() {
                                        navigator.clipboard.writeText('{{ $this->getMcpEndpoint() }}').then(() => {
                                            this.copied = true;
                                            setTimeout(() => this.copied = false, 2000);
                                        });
                                    }
                                }"
                                class="space-y-3"
                            >
                                <flux:field>
                                    <flux:label>MCP Server URL</flux:label>
                                    <flux:input
                                        type="text"
                                        readonly
                                        copyable
                                        :value="$this->getMcpEndpoint()"
                                        class:input="font-mono"
                                    />
                                </flux:field>
                            </div>

                            <div class="flex gap-4">
                                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-sm font-medium text-zinc-600 dark:text-zinc-400">4</span>
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-white">Authorize when prompted</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">A browser window will open for you to log in and authorize the connection</p>
                                </div>
                            </div>
                        @else
                            {{-- Claude CLI Instructions --}}
                            <p class="text-zinc-600 dark:text-zinc-400">
                                Run this command in your terminal to connect Claude Code to {{ config('app.name') }}:
                            </p>

                            <div
                                x-data="{
                                    copied: false,
                                    copy() {
                                        navigator.clipboard.writeText($wire.getCliCommand()).then(() => {
                                            this.copied = true;
                                            setTimeout(() => this.copied = false, 2000);
                                        });
                                    }
                                }"
                                class="space-y-3"
                            >
                                <div class="p-4 bg-zinc-900 rounded-lg overflow-x-auto">
                                    <code class="font-mono text-sm text-zinc-100 break-all whitespace-pre-wrap">{{ $this->getCliCommand() }}</code>
                                </div>

                                <flux:button @click="copy()" class="w-full">
                                    <flux:icon.document-duplicate x-show="!copied" variant="outline" class="w-4 h-4" />
                                    <flux:icon.check x-show="copied" class="w-4 h-4" />
                                    <span x-text="copied ? 'Copied!' : 'Copy Command'"></span>
                                </flux:button>
                            </div>

                            <div class="space-y-4">
                                <div class="flex gap-4">
                                    <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-sm font-medium text-zinc-600 dark:text-zinc-400">1</span>
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">Open your terminal</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Any terminal application will work</p>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-sm font-medium text-zinc-600 dark:text-zinc-400">2</span>
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">Run the command above</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Paste and execute the command to register the MCP server</p>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-sm font-medium text-zinc-600 dark:text-zinc-400">3</span>
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">Authorize in your browser</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">A browser window will open for you to log in and authorize the connection</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <flux:callout variant="info" icon="information-circle">
                            <flux:callout.heading>Secure OAuth Authentication</flux:callout.heading>
                            <flux:callout.text>
                                {{ config('app.name') }} uses OAuth for secure authentication. You'll be redirected to log in and authorize Claude to access your workouts.
                            </flux:callout.text>
                        </flux:callout>

                        <flux:button wire:click="goToStep(3)" variant="primary">
                            Continue to Step 3
                        </flux:button>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>

            {{-- Step 3: Start Training --}}
            <flux:accordion.item :expanded="$currentStep === 3">
                <flux:accordion.heading>
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-8 h-8 rounded-full {{ $currentStep === 3 ? 'bg-brand-lime text-black' : 'bg-zinc-200 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400' }} text-sm font-bold">
                            3
                        </span>
                        <span class="font-semibold text-zinc-900 dark:text-white">Start Your AI Intake</span>
                    </div>
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="pl-11 space-y-6">
                        <p class="text-zinc-600 dark:text-zinc-400">
                            You're all set! Start a conversation with Claude to create your personalized fitness profile and first workout plan.
                        </p>

                        <div class="space-y-4">
                            <h4 class="font-medium text-zinc-900 dark:text-white">Try saying something like:</h4>

                            <div class="space-y-3">
                                <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                                    <p class="text-zinc-700 dark:text-zinc-300 italic">
                                        "I want to start training for a 10K race in 3 months. I can train 4 days a week for about 45 minutes each session. Can you help me set up my fitness profile?"
                                    </p>
                                </div>

                                <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                                    <p class="text-zinc-700 dark:text-zinc-300 italic">
                                        "I'm recovering from a knee injury and want to get back into strength training gradually. What information do you need from me?"
                                    </p>
                                </div>

                                <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                                    <p class="text-zinc-700 dark:text-zinc-300 italic">
                                        "Create a weekly workout schedule for me focused on building muscle. I have access to a full gym."
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <h4 class="font-medium text-zinc-900 dark:text-white">What to expect:</h4>
                            <ul class="space-y-2 text-zinc-600 dark:text-zinc-400">
                                <li class="flex items-start gap-2">
                                    <flux:icon.check class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" />
                                    <span>Claude will ask about your fitness goals, available time, and any injuries</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <flux:icon.check class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" />
                                    <span>Your profile and workouts are saved automatically to {{ config('app.name') }}</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <flux:icon.check class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" />
                                    <span>View your schedule and track progress in your dashboard</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <flux:icon.check class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" />
                                    <span>Claude adapts your plan based on your feedback and progress</span>
                                </li>
                            </ul>
                        </div>

                        @auth
                            <div class="flex gap-4">
                                <a href="{{ route('dashboard') }}" class="bg-brand-lime text-black font-semibold px-6 py-3 rounded-lg transition-all hover:opacity-90">
                                    Go to Dashboard
                                </a>
                            </div>
                        @endauth
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>
        </flux:accordion>
    </div>

    {{-- Troubleshooting Section --}}
    <div class="mt-12 pt-8 border-t border-zinc-200 dark:border-zinc-800">
        <flux:accordion>
            <flux:accordion.item>
                <flux:accordion.heading>
                    <span class="text-zinc-600 dark:text-zinc-400">Troubleshooting</span>
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="space-y-4 text-sm text-zinc-600 dark:text-zinc-400">
                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white mb-2">"Authorization failed" or "OAuth error"</h4>
                            <p>Make sure you're logged into {{ config('app.name') }} in your browser and try the authorization again. Clear your browser cookies if the issue persists.</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white mb-2">"Connection refused" or "Cannot connect to server"</h4>
                            <p>Verify the MCP endpoint URL is correct: <code class="px-1.5 py-0.5 bg-zinc-100 dark:bg-zinc-800 rounded">{{ $this->getMcpEndpoint() }}</code></p>
                        </div>

                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white mb-2">Claude doesn't recognize the {{ config('app.name') }} commands</h4>
                            <p>Restart Claude after adding the MCP configuration. The server needs to be loaded fresh.</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white mb-2">Need more help?</h4>
                            <p>Contact support at <a href="mailto:support@traiq.io" class="text-brand-lime hover:underline">support@traiq.io</a></p>
                        </div>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>
        </flux:accordion>
    </div>
</div>
