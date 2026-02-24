<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', [
        'title' => config('app.name') . ' — Train Smarter, Not Harder',
        'description' => 'Your AI assistant becomes your personal trainer. Adaptive training plans, injury-safe programming, and Garmin-ready workouts — all through conversation.',
    ])
    <link href="https://fonts.bunny.net/css?family=outfit:400,500,600" rel="stylesheet" />
</head>
<body class="bg-black text-white font-['Outfit'] antialiased overflow-x-hidden">

    {{-- Navigation — transparent over hero, blurs on scroll --}}
    <nav id="traiq-nav" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
        <div class="flex items-center justify-between px-6 py-5 max-w-7xl mx-auto">
            <a href="{{ route('home') }}" class="text-white">
                <x-app-logo />
            </a>
            <div class="flex items-center gap-6">
                <a href="{{ route('login') }}" class="text-sm text-zinc-300 hover:text-white transition-colors font-medium">
                    Log in
                </a>
                <a href="{{ route('register') }}" class="traiq-cta px-5 py-2.5 rounded-lg text-sm">
                    Get Started
                </a>
            </div>
        </div>
    </nav>

    {{-- Hero — full viewport --}}
    <section class="relative min-h-screen flex items-center overflow-hidden">
        {{-- Background layers --}}
        <div class="absolute inset-0 bg-black traiq-noise traiq-hero-grid" aria-hidden="true">
            {{-- Diagonal geometric shapes --}}
            <div class="absolute inset-0">
                <div class="absolute top-0 right-0 w-2/3 h-full bg-zinc-900/40 origin-top-right" style="clip-path: polygon(30% 0, 100% 0, 100% 100%, 0 100%)"></div>
                <div class="absolute bottom-0 left-0 w-1/2 h-1/3 bg-zinc-900/20" style="clip-path: polygon(0 40%, 100% 0, 100% 100%, 0 100%)"></div>
            </div>
            {{-- Speed line --}}
            <div class="traiq-speed-line top-1/3 -left-1/4" aria-hidden="true"></div>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-6 pt-32 pb-20 w-full">
            <div class="max-w-3xl">
                <span class="inline-block text-xs font-semibold uppercase tracking-[0.2em] text-brand-lime mb-6 traiq-fade-in">
                    AI-Powered Fitness
                </span>

                <h1 class="font-['Bebas_Neue'] text-7xl sm:text-8xl md:text-9xl leading-[0.9] tracking-tight traiq-fade-in-delay-1">
                    Train Smarter,<br>
                    <span class="text-brand-lime">Not Harder</span>
                </h1>

                <p class="mt-8 text-lg md:text-xl text-zinc-400 max-w-xl leading-relaxed font-['Outfit'] traiq-fade-in-delay-2">
                    Chat with your AI coach, get personalized workouts, and track every rep. Injury-safe programming, smart analytics, and Garmin-ready exports &mdash; no setup required.
                </p>

                <div class="mt-10 flex flex-col sm:flex-row items-start gap-4 traiq-fade-in-delay-3">
                    <a href="{{ route('register') }}" class="traiq-cta px-8 py-4 rounded-lg text-base">
                        Get Started Free
                    </a>
                    <span class="text-sm text-zinc-500 self-center">Free to get started. No credit card required.</span>
                </div>
            </div>
        </div>

        {{-- Scroll indicator --}}
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 traiq-fade-in-delay-4">
            <span class="text-xs text-zinc-500 tracking-widest uppercase font-medium">Scroll</span>
            <svg class="w-4 h-4 text-zinc-500 animate-bounce" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
            </svg>
        </div>
    </section>

    {{-- Trust Strip --}}
    <section class="border-y border-zinc-800 bg-zinc-950">
        <div class="max-w-7xl mx-auto px-6 py-6">
            <div class="flex flex-col sm:flex-row items-center justify-center gap-8 sm:gap-16">
                <div class="flex items-center gap-3">
                    <span class="text-brand-lime font-['Bebas_Neue'] text-2xl">2,025</span>
                    <span class="text-xs uppercase tracking-widest text-zinc-400">Exercises in the Library</span>
                </div>
                <div class="hidden sm:block w-px h-6 bg-zinc-800"></div>
                <div class="flex items-center gap-3">
                    <span class="text-brand-lime font-['Bebas_Neue'] text-2xl">17</span>
                    <span class="text-xs uppercase tracking-widest text-zinc-400">Muscle Groups Tracked</span>
                </div>
                <div class="hidden sm:block w-px h-6 bg-zinc-800"></div>
                <div class="flex items-center gap-3">
                    <span class="text-brand-lime font-['Bebas_Neue'] text-2xl">79</span>
                    <span class="text-xs uppercase tracking-widest text-zinc-400">Activity Types Supported</span>
                </div>
            </div>
        </div>
    </section>

    {{-- How It Works --}}
    <section class="bg-black py-28">
        <div class="max-w-7xl mx-auto px-6">
            <div class="mb-16 traiq-reveal">
                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-lime">How it works</span>
                <h2 class="mt-4 font-['Bebas_Neue'] text-5xl md:text-6xl text-white">
                    Two steps to smarter training
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-12 relative">
                {{-- Connecting line (desktop only) --}}
                <div class="hidden md:block absolute top-12 left-[16.67%] right-[16.67%] h-px bg-zinc-800" aria-hidden="true"></div>

                {{-- Step 1 --}}
                <div class="relative traiq-reveal">
                    <span class="font-['Bebas_Neue'] text-6xl text-brand-lime/20 leading-none">01</span>
                    <h3 class="mt-4 text-xl font-semibold text-white">Sign Up</h3>
                    <p class="mt-3 text-zinc-400 leading-relaxed">
                        Create your free account in under 60 seconds. Set your fitness profile, track active injuries, and tell your coach about your equipment and schedule.
                    </p>
                </div>

                {{-- Step 2 --}}
                <div class="relative traiq-reveal" style="transition-delay: 0.1s">
                    <span class="font-['Bebas_Neue'] text-6xl text-brand-lime/20 leading-none">02</span>
                    <h3 class="mt-4 text-xl font-semibold text-white">Chat with Your Coach</h3>
                    <p class="mt-3 text-zinc-400 leading-relaxed">
                        Open the built-in AI coach and describe your goals. Whether it's injury rehab, race prep, or strength gains &mdash; get structured workouts from a 2,025-exercise library.
                    </p>
                </div>

                {{-- Step 3 --}}
                <div class="relative traiq-reveal" style="transition-delay: 0.2s">
                    <span class="font-['Bebas_Neue'] text-6xl text-brand-lime/20 leading-none">03</span>
                    <h3 class="mt-4 text-xl font-semibold text-white">Train, Track, Adapt</h3>
                    <p class="mt-3 text-zinc-400 leading-relaxed">
                        Follow your workouts, log performance, and get real-time feedback. Session load alerts, muscle group volume trends, and 1RM progression keep you training safely.
                    </p>
                </div>
            </div>

            <div class="mt-12 p-6 rounded-lg border border-zinc-800 traiq-reveal">
                <p class="text-zinc-400">
                    <span class="text-brand-lime font-semibold">Power users:</span> Connect your own AI via MCP for Claude Desktop, ChatGPT, Cursor, or any MCP-compatible client.
                    <a href="{{ route('get-started') }}" class="inline-flex items-center gap-1 text-brand-lime hover:text-brand-lime/80 font-medium transition-colors ml-1">
                        See setup guide
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </p>
            </div>
        </div>
    </section>

    {{-- Features / Why Traiq --}}
    <section class="bg-zinc-50 py-28">
        <div class="max-w-7xl mx-auto px-6">
            <div class="mb-16 traiq-reveal">
                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500">Why {{ config('app.name') }}</span>
                <h2 class="mt-4 font-['Bebas_Neue'] text-5xl md:text-6xl text-black">
                    Training that works for you
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-8">
                {{-- Injury-Aware --}}
                <div class="bg-white border-l-4 border-l-brand-lime rounded-lg p-8 traiq-reveal">
                    <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Injury-Aware</span>
                    <h3 class="mt-3 text-2xl font-bold text-black leading-tight">Your AI Knows Your Body</h3>
                    <p class="mt-4 text-zinc-600 leading-relaxed">
                        Track injuries across 22 body parts with 4 injury types. Your AI references your injury history and avoids aggravating movements.
                    </p>
                    <ul class="mt-6 pt-5 space-y-2.5 border-t border-zinc-100">
                        <li class="flex items-center gap-2 text-sm text-zinc-600">
                            <svg class="w-4 h-4 text-brand-lime flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            22 trackable body parts
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-600">
                            <svg class="w-4 h-4 text-brand-lime flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            Daily injury status reports
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-600">
                            <svg class="w-4 h-4 text-brand-lime flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            Smart exercise substitutions
                        </li>
                    </ul>
                </div>

                {{-- Smart Analytics --}}
                <div class="bg-white border-l-4 border-l-brand-lime rounded-lg p-8 traiq-reveal" style="transition-delay: 0.1s">
                    <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Smart Analytics</span>
                    <h3 class="mt-3 text-2xl font-bold text-black leading-tight">Evidence-Based Progression</h3>
                    <p class="mt-4 text-zinc-600 leading-relaxed">
                        Three pillars of training intelligence: session load tracking with monotony and strain alerts, per-muscle-group volume with 4-week trends, and estimated 1RM progression via the Epley formula.
                    </p>
                    <ul class="mt-6 pt-5 space-y-2.5 border-t border-zinc-100">
                        <li class="flex items-center gap-2 text-sm text-zinc-600">
                            <svg class="w-4 h-4 text-brand-lime flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            sRPE load tracking with spike alerts
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-600">
                            <svg class="w-4 h-4 text-brand-lime flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            17 muscle groups with volume trends
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-600">
                            <svg class="w-4 h-4 text-brand-lime flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            Estimated 1RM strength tracking
                        </li>
                    </ul>
                </div>

                {{-- 9 Block Types --}}
                <div class="bg-white border-l-4 border-l-brand-lime rounded-lg p-8 traiq-reveal" style="transition-delay: 0.2s">
                    <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">9 Block Types</span>
                    <h3 class="mt-3 text-2xl font-bold text-black leading-tight">Every Workout Structure</h3>
                    <p class="mt-4 text-zinc-600 leading-relaxed">
                        Straight sets, circuits, supersets, AMRAP, EMOM, intervals, and more. Three exercise modes: strength, cardio, and duration.
                    </p>
                    <ul class="mt-6 pt-5 space-y-2.5 border-t border-zinc-100">
                        <li class="flex items-center gap-2 text-sm text-zinc-600">
                            <svg class="w-4 h-4 text-brand-lime flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            2,025 exercises in the library
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-600">
                            <svg class="w-4 h-4 text-brand-lime flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            RPE-based autoregulation
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-600">
                            <svg class="w-4 h-4 text-brand-lime flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            Tempo prescriptions
                        </li>
                    </ul>
                </div>

                {{-- Garmin-Ready --}}
                <div class="bg-white border-l-4 border-l-brand-lime rounded-lg p-8 traiq-reveal" style="transition-delay: 0.3s">
                    <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Device Integration</span>
                    <h3 class="mt-3 text-2xl font-bold text-black leading-tight">Garmin-Ready Workouts</h3>
                    <p class="mt-4 text-zinc-600 leading-relaxed">
                        Export workouts directly to your Garmin watch. 1,180 exercises mapped to Garmin exercise types with full FIT file support.
                    </p>
                    <ul class="mt-6 pt-5 space-y-2.5 border-t border-zinc-100">
                        <li class="flex items-center gap-2 text-sm text-zinc-600">
                            <svg class="w-4 h-4 text-brand-lime flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            1,180 mapped exercises
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-600">
                            <svg class="w-4 h-4 text-brand-lime flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            FIT file export
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-600">
                            <svg class="w-4 h-4 text-brand-lime flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            Follow-along on your wrist
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Use Cases --}}
    <section class="bg-zinc-950 py-28">
        <div class="max-w-7xl mx-auto px-6">
            <div class="mb-16 traiq-reveal">
                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-lime">Perfect for</span>
                <h2 class="mt-4 font-['Bebas_Neue'] text-5xl md:text-6xl text-white">
                    For every fitness goal
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                <div class="border border-zinc-800 rounded-lg p-6 hover:border-brand-lime/30 transition-colors traiq-reveal">
                    <span class="text-xs font-semibold uppercase tracking-wide text-brand-lime">Injury-Safe</span>
                    <h3 class="mt-3 text-xl font-bold text-white">Returning from injury</h3>
                    <p class="mt-2 text-sm text-zinc-400">
                        Your AI references your injury history and avoids aggravating movements. Smart substitutions and gradual progression built in.
                    </p>
                </div>

                <div class="border border-zinc-800 rounded-lg p-6 hover:border-brand-lime/30 transition-colors traiq-reveal" style="transition-delay: 0.1s">
                    <span class="text-xs font-semibold uppercase tracking-wide text-brand-lime">Race Prep</span>
                    <h3 class="mt-3 text-xl font-bold text-white">Your first 5K</h3>
                    <p class="mt-2 text-sm text-zinc-400">
                        Get a periodized training plan with pacing targets and heart rate zones. Built for beginners, backed by sports science.
                    </p>
                </div>

                <div class="border border-zinc-800 rounded-lg p-6 hover:border-brand-lime/30 transition-colors traiq-reveal" style="transition-delay: 0.2s">
                    <span class="text-xs font-semibold uppercase tracking-wide text-brand-lime">Time-Efficient</span>
                    <h3 class="mt-3 text-xl font-bold text-white">Limited time, maximum results</h3>
                    <p class="mt-2 text-sm text-zinc-400">
                        Supersets, circuits, and compound movements optimized for your available time. Every minute counts.
                    </p>
                </div>

                <div class="border border-zinc-800 rounded-lg p-6 hover:border-brand-lime/30 transition-colors traiq-reveal" style="transition-delay: 0.3s">
                    <span class="text-xs font-semibold uppercase tracking-wide text-brand-lime">Advanced</span>
                    <h3 class="mt-3 text-xl font-bold text-white">Programming for serious lifters</h3>
                    <p class="mt-2 text-sm text-zinc-400">
                        RPE autoregulation, tempo prescriptions, periodization cycles, and deload weeks. Training that matches your experience.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="bg-black py-28 border-t border-zinc-800">
        <div class="max-w-3xl mx-auto px-6">
            <div class="mb-12 traiq-reveal">
                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-lime">FAQ</span>
                <h2 class="mt-4 font-['Bebas_Neue'] text-5xl md:text-6xl text-white">
                    Common questions
                </h2>
            </div>

            <div class="traiq-reveal [&_[data-flux-accordion-heading]]:text-white [&_[data-flux-accordion-content]]:text-zinc-400 [&_[data-flux-accordion-heading]]:border-zinc-800 [&_[data-flux-accordion-item]]:border-zinc-800">
                <flux:accordion transition>
                    <flux:accordion.item>
                        <flux:accordion.heading>
                            What's the difference between using Traiq with Claude vs ChatGPT?
                        </flux:accordion.heading>
                        <flux:accordion.content>
                            <p class="text-zinc-400 leading-relaxed">
                                Functionally, they're identical. {{ config('app.name') }} uses the MCP (Model Context Protocol) standard, which means every AI client gets access to the same tools and data. Your workout library, injury tracking, and analytics work the same way regardless of which AI you use.
                            </p>
                        </flux:accordion.content>
                    </flux:accordion.item>

                    <flux:accordion.item>
                        <flux:accordion.heading>
                            Is my workout data sent to Claude or ChatGPT?
                        </flux:accordion.heading>
                        <flux:accordion.content>
                            <p class="text-zinc-400 leading-relaxed">
                                Your data stays in {{ config('app.name') }}. The AI only sees what it needs for each specific request &mdash; like your current workout plan or injury status. {{ config('app.name') }} acts as a secure bridge between you and your AI assistant using OAuth 2.1 authentication.
                            </p>
                        </flux:accordion.content>
                    </flux:accordion.item>

                    <flux:accordion.item>
                        <flux:accordion.heading>
                            Can I use Traiq without Claude or ChatGPT?
                        </flux:accordion.heading>
                        <flux:accordion.content>
                            <p class="text-zinc-400 leading-relaxed">
                                Yes. {{ config('app.name') }} includes a built-in AI coach that handles workout creation, progress tracking, and training advice. You don't need Claude, ChatGPT, or any external AI. Power users who prefer their own AI tools can optionally connect via MCP (Model Context Protocol) for advanced workflows.
                            </p>
                        </flux:accordion.content>
                    </flux:accordion.item>
                </flux:accordion>
            </div>
        </div>
    </section>

    {{-- Footer CTA --}}
    <section class="relative bg-black py-28 overflow-hidden">
        {{-- Accent line at top --}}
        <div class="absolute top-0 left-0 right-0 h-px bg-brand-lime/30" aria-hidden="true"></div>

        <div class="relative z-10 max-w-4xl mx-auto px-6 text-center">
            <h2 class="font-['Bebas_Neue'] text-6xl sm:text-7xl md:text-8xl text-white leading-[0.9] traiq-reveal">
                Ready to train<br><span class="text-brand-lime">smarter?</span>
            </h2>
            <p class="mt-6 text-zinc-400 text-lg max-w-xl mx-auto traiq-reveal">
                Free to get started, no credit card required. Chat with your built-in AI coach, or connect your own AI via MCP &mdash; and get training in minutes.
            </p>
            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4 traiq-reveal">
                <a href="{{ route('register') }}" class="traiq-cta px-8 py-4 rounded-lg text-base">
                    Get Started Free
                </a>
                <a href="{{ route('get-started') }}" class="inline-flex items-center gap-2 text-zinc-400 hover:text-white font-medium transition-colors">
                    View setup guide
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </div>
        </div>

        {{-- Footer links --}}
        <div class="mt-20 pt-8 border-t border-zinc-900 max-w-7xl mx-auto px-6">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-zinc-600">
                <span>&copy; {{ date('Y') }} {{ config('app.name') }}</span>
                <div class="flex items-center gap-6">
                    <a href="{{ route('login') }}" class="hover:text-zinc-400 transition-colors">Log in</a>
                    <a href="{{ route('register') }}" class="hover:text-zinc-400 transition-colors">Sign up</a>
                    <a href="{{ route('get-started') }}" class="hover:text-zinc-400 transition-colors">Get started</a>
                </div>
            </div>
        </div>
    </section>

</body>
</html>
