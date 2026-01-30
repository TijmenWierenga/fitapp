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
                            ? 'bg-brand-red text-white'
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
                            @case(1) Account & API Key @break
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
        {{-- Step 1: Account & API Key --}}
        <flux:accordion exclusive wire:key="accordion-{{ $currentStep }}">
            <flux:accordion.item :expanded="$currentStep === 1">
                <flux:accordion.heading>
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-8 h-8 rounded-full {{ $currentStep > 1 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-brand-red text-white' }} text-sm font-bold">
                            @if ($currentStep > 1)
                                <flux:icon.check variant="mini" class="w-5 h-5" />
                            @else
                                1
                            @endif
                        </span>
                        <span class="font-semibold text-zinc-900 dark:text-white">Create an Account & API Key</span>
                    </div>
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="pl-11 space-y-6">
                        @guest
                            <flux:callout variant="info" icon="information-circle">
                                <flux:callout.heading>Create an account first</flux:callout.heading>
                                <flux:callout.text>
                                    You'll need a {{ config('app.name') }} account to generate an API key for Claude.
                                </flux:callout.text>
                            </flux:callout>

                            <div class="flex gap-4">
                                <a href="{{ route('register') }}" class="traiq-cta-gradient text-white font-semibold px-6 py-3 rounded-lg transition-all">
                                    Create Account
                                </a>
                                <a href="{{ route('login') }}" class="border border-zinc-300 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 font-semibold px-6 py-3 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-all">
                                    Log In
                                </a>
                            </div>
                        @else
                            @if ($tokenCreated && $newToken)
                                <flux:callout variant="success" icon="check-circle">
                                    <flux:callout.heading>API Key Created!</flux:callout.heading>
                                    <flux:callout.text>
                                        Your API key has been created. Copy it now - you won't be able to see it again.
                                    </flux:callout.text>
                                </flux:callout>

                                <flux:input
                                    type="text"
                                    readonly
                                    copyable
                                    :value="$newToken"
                                    class:input="font-mono"
                                />

                                <flux:button wire:click="goToStep(2)" variant="primary">
                                    Continue to Step 2
                                </flux:button>
                            @else
                                <p class="text-zinc-600 dark:text-zinc-400">
                                    Create an API key that Claude will use to manage your workouts. This key authenticates Claude's requests to {{ config('app.name') }}.
                                </p>

                                <flux:callout variant="warning" icon="exclamation-triangle">
                                    <flux:callout.heading>Save your key securely</flux:callout.heading>
                                    <flux:callout.text>
                                        Your API key will only be shown once. Make sure to copy it to a secure location.
                                    </flux:callout.text>
                                </flux:callout>

                                @error('tokenLimit')
                                    <flux:callout variant="danger" icon="x-circle">
                                        <flux:callout.text>{{ $message }} <a href="{{ route('api-keys.index') }}" class="underline">Manage API Keys</a></flux:callout.text>
                                    </flux:callout>
                                @enderror

                                <form wire:submit="createToken" class="space-y-4">
                                    <flux:field>
                                        <flux:label>API Key Name</flux:label>
                                        <flux:input
                                            wire:model="tokenName"
                                            placeholder="e.g., Claude Code"
                                        />
                                        <flux:description>A name to help you identify this key later.</flux:description>
                                        <flux:error name="tokenName" />
                                    </flux:field>

                                    <flux:button type="submit" variant="primary" icon="key">
                                        Create API Key
                                    </flux:button>
                                </form>
                            @endif
                        @endguest
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>

            {{-- Step 2: Configure Claude --}}
            <flux:accordion.item :expanded="$currentStep === 2">
                <flux:accordion.heading>
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-8 h-8 rounded-full {{ $currentStep > 2 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : ($currentStep === 2 ? 'bg-brand-red text-white' : 'bg-zinc-200 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400') }} text-sm font-bold">
                            @if ($currentStep > 2)
                                <flux:icon.check variant="mini" class="w-5 h-5" />
                            @else
                                2
                            @endif
                        </span>
                        <span class="font-semibold text-zinc-900 dark:text-white">Configure Claude Code</span>
                    </div>
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="pl-11 space-y-6">
                        <p class="text-zinc-600 dark:text-zinc-400">
                            Add the following MCP server configuration to your Claude Code settings. This connects Claude to your {{ config('app.name') }} account.
                        </p>

                        <div class="space-y-4">
                            <h4 class="font-medium text-zinc-900 dark:text-white">1. Open Claude Code settings</h4>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                In Claude Code, open Settings (press <code class="px-1.5 py-0.5 bg-zinc-100 dark:bg-zinc-800 rounded">Cmd/Ctrl + ,</code>) and navigate to the MCP Servers section.
                            </p>
                        </div>

                        <div class="space-y-4">
                            <h4 class="font-medium text-zinc-900 dark:text-white">2. Add this MCP server configuration</h4>

                            <div
                                x-data="{
                                    copied: false,
                                    copy() {
                                        navigator.clipboard.writeText($wire.getConfigJson()).then(() => {
                                            this.copied = true;
                                            setTimeout(() => this.copied = false, 2000);
                                        });
                                    }
                                }"
                            >
                                <div class="relative">
                                    <pre class="p-4 bg-zinc-900 text-zinc-100 rounded-lg overflow-x-auto text-sm font-mono"><code>{{ $this->getConfigJson() }}</code></pre>
                                    <button
                                        type="button"
                                        @click="copy()"
                                        class="absolute top-3 right-3 px-3 py-1.5 bg-zinc-700 hover:bg-zinc-600 text-white text-sm rounded-md transition-colors flex items-center gap-2"
                                    >
                                        <flux:icon.document-duplicate x-show="!copied" variant="outline" class="w-4 h-4" />
                                        <flux:icon.check x-show="copied" class="w-4 h-4" />
                                        <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                                    </button>
                                </div>
                            </div>

                            @guest
                                <flux:callout variant="warning" icon="exclamation-triangle">
                                    <flux:callout.text>
                                        Replace <code class="px-1 bg-zinc-100 dark:bg-zinc-800 rounded">YOUR_API_KEY_HERE</code> with the API key you create in Step 1.
                                    </flux:callout.text>
                                </flux:callout>
                            @else
                                @if ($tokenCreated && $newToken)
                                    <flux:callout variant="success" icon="check-circle">
                                        <flux:callout.text>
                                            The configuration above already includes your new API key!
                                        </flux:callout.text>
                                    </flux:callout>
                                @else
                                    <flux:callout variant="info" icon="information-circle">
                                        <flux:callout.text>
                                            Complete Step 1 first to get your API key, then come back here.
                                        </flux:callout.text>
                                    </flux:callout>
                                @endif
                            @endguest
                        </div>

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
                        <span class="flex items-center justify-center w-8 h-8 rounded-full {{ $currentStep === 3 ? 'bg-brand-red text-white' : 'bg-zinc-200 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400' }} text-sm font-bold">
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
                                <a href="{{ route('dashboard') }}" class="traiq-cta-gradient text-white font-semibold px-6 py-3 rounded-lg transition-all">
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
                            <h4 class="font-medium text-zinc-900 dark:text-white mb-2">"Authentication failed" or "401 Unauthorized"</h4>
                            <p>Make sure your API key is correct and hasn't been revoked. You can check your active keys in <a href="{{ route('api-keys.index') }}" class="text-brand-red hover:underline">Settings > API Keys</a>.</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white mb-2">"Connection refused" or "Cannot connect to server"</h4>
                            <p>Verify the MCP endpoint URL is correct: <code class="px-1.5 py-0.5 bg-zinc-100 dark:bg-zinc-800 rounded">{{ $this->getMcpEndpoint() }}</code></p>
                        </div>

                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white mb-2">Claude doesn't recognize the {{ config('app.name') }} commands</h4>
                            <p>Restart Claude Code after adding the MCP configuration. The server needs to be loaded fresh.</p>
                        </div>

                        <div>
                            <h4 class="font-medium text-zinc-900 dark:text-white mb-2">Need more help?</h4>
                            <p>Contact support at <a href="mailto:support@traiq.io" class="text-brand-red hover:underline">support@traiq.io</a></p>
                        </div>
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>
        </flux:accordion>
    </div>
</div>
