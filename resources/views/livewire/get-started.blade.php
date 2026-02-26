<div>
    {{-- Hero Section --}}
    <div class="text-center mb-12">
        <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-white mb-4">
            Get Started with {{ config('app.name') }}
        </h1>
        <p class="text-lg text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto">
            Start training in minutes with the built-in AI coach, or connect your own AI via MCP.
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
                            @case(2) Configure Your AI @break
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
                            Choose how you'd like to get started with {{ config('app.name') }}.
                        </p>

                        {{-- In-App Chat Card — Primary Option --}}
                        <button
                            wire:click="selectMethod('chat')"
                            class="relative w-full p-6 rounded-xl border-2 text-left transition-all hover:border-brand-lime hover:bg-lime-50 dark:hover:bg-lime-950/20
                                {{ $setupMethod === 'chat' ? 'border-brand-lime bg-lime-50 dark:bg-lime-950/20' : 'border-zinc-200 dark:border-zinc-700' }}"
                        >
                            <span class="absolute top-3 right-3 px-2 py-0.5 text-xs font-medium bg-brand-lime text-black rounded-full">
                                Easiest
                            </span>
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-lg bg-brand-lime/20">
                                    <flux:icon.chat-bubble-bottom-center-text class="w-6 h-6 text-lime-700 dark:text-lime-400" />
                                </div>
                                <div>
                                    <h3 class="font-semibold text-zinc-900 dark:text-white mb-1">In-App Chat</h3>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        Start chatting with your AI coach right away &mdash; no external tools needed
                                    </p>
                                </div>
                            </div>
                        </button>

                        <div class="flex items-center gap-4">
                            <div class="flex-1 h-px bg-zinc-200 dark:bg-zinc-700"></div>
                            <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wide">Or connect your own AI</span>
                            <div class="flex-1 h-px bg-zinc-200 dark:bg-zinc-700"></div>
                        </div>

                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
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
                                            Add as connector in Claude Desktop settings
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

                            {{-- ChatGPT Desktop Option --}}
                            <button
                                wire:click="selectMethod('chatgpt')"
                                class="p-6 rounded-xl border-2 text-left transition-all hover:border-brand-lime hover:bg-zinc-50 dark:hover:bg-zinc-900
                                    {{ $setupMethod === 'chatgpt' ? 'border-brand-lime bg-zinc-50 dark:bg-zinc-900' : 'border-zinc-200 dark:border-zinc-700' }}"
                            >
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon.chat-bubble-left-right class="w-6 h-6 text-zinc-600 dark:text-zinc-400" />
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-zinc-900 dark:text-white mb-1">ChatGPT Desktop</h3>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                            Add as extension in ChatGPT settings
                                        </p>
                                    </div>
                                </div>
                            </button>

                            {{-- VS Code / Copilot Option --}}
                            <button
                                wire:click="selectMethod('vscode')"
                                class="p-6 rounded-xl border-2 text-left transition-all hover:border-brand-lime hover:bg-zinc-50 dark:hover:bg-zinc-900
                                    {{ $setupMethod === 'vscode' ? 'border-brand-lime bg-zinc-50 dark:bg-zinc-900' : 'border-zinc-200 dark:border-zinc-700' }}"
                            >
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon.code-bracket class="w-6 h-6 text-zinc-600 dark:text-zinc-400" />
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-zinc-900 dark:text-white mb-1">VS Code / Copilot</h3>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                            Add MCP server to VS Code settings
                                        </p>
                                    </div>
                                </div>
                            </button>

                            {{-- Other MCP Client Option --}}
                            <button
                                wire:click="selectMethod('other')"
                                class="p-6 rounded-xl border-2 text-left transition-all hover:border-brand-lime hover:bg-zinc-50 dark:hover:bg-zinc-900
                                    {{ $setupMethod === 'other' ? 'border-brand-lime bg-zinc-50 dark:bg-zinc-900' : 'border-zinc-200 dark:border-zinc-700' }}"
                            >
                                <div class="flex items-start gap-4">
                                    <div class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-800">
                                        <flux:icon.ellipsis-horizontal-circle class="w-6 h-6 text-zinc-600 dark:text-zinc-400" />
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-zinc-900 dark:text-white mb-1">Other MCP Client</h3>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                            Cursor, Gemini CLI, Windsurf, and more
                                        </p>
                                    </div>
                                </div>
                            </button>
                        </div>

                        @if ($setupMethod !== 'chat')
                            <flux:button wire:click="goToStep(2)" variant="primary">
                                Continue with {{ $this->getMethodLabel() }}
                            </flux:button>
                        @endif
                    </div>
                </flux:accordion.content>
            </flux:accordion.item>

            {{-- Step 2: Configure Your AI --}}
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
                        <span class="font-semibold text-zinc-900 dark:text-white">Configure Your AI</span>
                    </div>
                </flux:accordion.heading>
                <flux:accordion.content>
                    <div class="pl-11 space-y-6">
                        @if ($setupMethod === 'desktop')
                            {{-- Claude Desktop Instructions --}}
                            <p class="text-zinc-600 dark:text-zinc-400">
                                Follow these steps to add {{ config('app.name') }} as a connector in Claude Desktop:
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

                            <div class="space-y-3">
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
                        @elseif ($setupMethod === 'cli')
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
                        @elseif ($setupMethod === 'chatgpt')
                            {{-- ChatGPT Desktop Instructions --}}
                            <p class="text-zinc-600 dark:text-zinc-400">
                                Follow these steps to add {{ config('app.name') }} as an extension in ChatGPT Desktop:
                            </p>

                            <div class="space-y-4">
                                <div class="flex gap-4">
                                    <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-sm font-medium text-zinc-600 dark:text-zinc-400">1</span>
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">Open ChatGPT Desktop Settings</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Click on your profile icon and select "Settings"</p>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-sm font-medium text-zinc-600 dark:text-zinc-400">2</span>
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">Navigate to Extensions</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Find "Extensions" or "MCP Servers" in the settings menu</p>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-sm font-medium text-zinc-600 dark:text-zinc-400">3</span>
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">Add Custom Connector</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Click "Add" and paste the MCP server URL below:</p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3">
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
                        @elseif ($setupMethod === 'vscode')
                            {{-- VS Code / Copilot Instructions --}}
                            <p class="text-zinc-600 dark:text-zinc-400">
                                Add {{ config('app.name') }} as an MCP server in VS Code settings:
                            </p>

                            <div class="space-y-4">
                                <div class="flex gap-4">
                                    <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-sm font-medium text-zinc-600 dark:text-zinc-400">1</span>
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">Open VS Code Settings</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Press <kbd>Cmd</kbd>+<kbd>,</kbd> (Mac) or <kbd>Ctrl</kbd>+<kbd>,</kbd> (Windows/Linux) to open settings</p>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-sm font-medium text-zinc-600 dark:text-zinc-400">2</span>
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">Open settings.json</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Click the "Open Settings (JSON)" icon in the top right, then add:</p>
                                    </div>
                                </div>
                            </div>

                            <div
                                x-data="{
                                    copied: false,
                                    snippet: JSON.stringify({ 'mcp': { 'servers': { 'traiq': { 'type': 'http', 'url': '{{ $this->getMcpEndpoint() }}' } } } }, null, 2),
                                    copy() {
                                        navigator.clipboard.writeText(this.snippet).then(() => {
                                            this.copied = true;
                                            setTimeout(() => this.copied = false, 2000);
                                        });
                                    }
                                }"
                                class="space-y-3"
                            >
                                <div class="p-4 bg-zinc-900 rounded-lg overflow-x-auto">
                                    <pre class="font-mono text-sm text-zinc-100 whitespace-pre" x-text="snippet"></pre>
                                </div>

                                <flux:button @click="copy()" class="w-full">
                                    <flux:icon.document-duplicate x-show="!copied" variant="outline" class="w-4 h-4" />
                                    <flux:icon.check x-show="copied" class="w-4 h-4" />
                                    <span x-text="copied ? 'Copied!' : 'Copy Configuration'"></span>
                                </flux:button>
                            </div>

                            <div class="flex gap-4">
                                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-sm font-medium text-zinc-600 dark:text-zinc-400">3</span>
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-white">Authorize when prompted</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">VS Code will prompt you to authorize the connection in your browser</p>
                                </div>
                            </div>
                        @else
                            {{-- Other MCP Client Instructions --}}
                            <p class="text-zinc-600 dark:text-zinc-400">
                                {{ config('app.name') }} works with any MCP-compatible client. Find your client's MCP settings and add the server URL below.
                            </p>

                            <div class="space-y-4">
                                <div class="flex gap-4">
                                    <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-sm font-medium text-zinc-600 dark:text-zinc-400">1</span>
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">Open your AI client's settings</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Look for "MCP", "Extensions", "Connectors", or "Tools" in the settings menu</p>
                                    </div>
                                </div>

                                <div class="flex gap-4">
                                    <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-sm font-medium text-zinc-600 dark:text-zinc-400">2</span>
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">Add a new MCP server</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Add a new server with the URL below:</p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3">
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
                                <span class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800 text-sm font-medium text-zinc-600 dark:text-zinc-400">3</span>
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-white">Authorize via OAuth</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">When prompted, log in to {{ config('app.name') }} and authorize the connection</p>
                                </div>
                            </div>
                        @endif

                        <flux:callout variant="success" icon="shield-check">
                            <flux:callout.heading>Secure OAuth 2.1 Authentication</flux:callout.heading>
                            <flux:callout.text>
                                {{ config('app.name') }} uses OAuth 2.1 for secure authentication. Your credentials are never shared with the AI client. You'll be redirected to log in and authorize access.
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
                            You're all set! Start a conversation with your AI to create your personalized fitness profile and first workout plan.
                        </p>

                        <flux:callout color="blue" icon="sparkles">
                            <flux:callout.heading>What your AI can do with {{ config('app.name') }}</flux:callout.heading>
                            <flux:callout.text>
                                <ul class="mt-1 space-y-1.5">
                                    <li>Create structured workout plans from 2,025 exercises</li>
                                    <li>Track injuries and avoid aggravating movements</li>
                                    <li>Monitor training load, muscle group volume, and strength progression</li>
                                    <li>Export workouts to your Garmin watch</li>
                                    <li>Adapt your plan based on feedback and progress</li>
                                </ul>
                            </flux:callout.text>
                        </flux:callout>

                        <div class="space-y-4">
                            <h4 class="font-medium text-zinc-900 dark:text-white">Try one of these prompts to get started:</h4>

                            <div class="space-y-3">
                                <div>
                                    <span class="text-xs font-semibold uppercase tracking-wide text-brand-lime">Beginner</span>
                                    <div class="mt-1 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                                        <p class="text-zinc-700 dark:text-zinc-300 italic">
                                            "I want to train for a 10K race. I can train 4 days a week and I'm a complete beginner. Can you help me set up my profile and create a training plan?"
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <span class="text-xs font-semibold uppercase tracking-wide text-brand-lime">Intermediate</span>
                                    <div class="mt-1 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                                        <p class="text-zinc-700 dark:text-zinc-300 italic">
                                            "I'm recovering from a knee injury and want to get back into strength training. I have a home gym with dumbbells and a bench. Help me return gradually."
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <span class="text-xs font-semibold uppercase tracking-wide text-brand-lime">Advanced</span>
                                    <div class="mt-1 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-800">
                                        <p class="text-zinc-700 dark:text-zinc-300 italic">
                                            "Design a 12-week hypertrophy program with RPE autoregulation, session load monitoring for recovery management, and scheduled deload weeks."
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @auth
                            <div class="flex flex-wrap gap-4">
                                <a href="{{ route('coach', ['intake' => 1]) }}" class="bg-brand-lime text-black font-semibold px-6 py-3 rounded-lg transition-all hover:opacity-90">
                                    Try the In-App Coach
                                </a>
                                <a href="{{ route('dashboard') }}" class="border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 font-semibold px-6 py-3 rounded-lg transition-all hover:bg-zinc-50 dark:hover:bg-zinc-800">
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
                            <h4 class="font-medium text-zinc-900 dark:text-white mb-2">Your AI doesn't recognize the {{ config('app.name') }} commands</h4>
                            <p>Restart your AI client after adding the MCP configuration. The server needs to be loaded fresh.</p>
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
