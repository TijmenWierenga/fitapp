<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', [
        'title' => config('app.name') . ' — Train Smarter, Not Harder',
        'description' => 'AI-powered fitness coaching by Claude. Personalized, adaptive training plans through natural conversation.',
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
                    {{ config('app.name') }} connects Claude to your fitness journey. Get personalized, adaptive training plans through natural conversation.
                </p>

                <div class="mt-10 flex flex-col sm:flex-row items-start gap-4 traiq-fade-in-delay-3">
                    <a href="{{ route('register') }}" class="traiq-cta px-8 py-4 rounded-lg text-base">
                        Get Started Free
                    </a>
                    <span class="text-sm text-zinc-500 self-center">Free during early access</span>
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
                    <span class="text-brand-lime font-['Bebas_Neue'] text-2xl">AI</span>
                    <span class="text-xs uppercase tracking-widest text-zinc-400">Powered by Claude</span>
                </div>
                <div class="hidden sm:block w-px h-6 bg-zinc-800"></div>
                <div class="flex items-center gap-3">
                    <span class="text-brand-lime font-['Bebas_Neue'] text-2xl">100%</span>
                    <span class="text-xs uppercase tracking-widest text-zinc-400">Adapts Every Session</span>
                </div>
                <div class="hidden sm:block w-px h-6 bg-zinc-800"></div>
                <div class="flex items-center gap-3">
                    <span class="text-brand-lime font-['Bebas_Neue'] text-2xl">0</span>
                    <span class="text-xs uppercase tracking-widest text-zinc-400">Config Required</span>
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
                    Three steps to smarter training
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-12 relative">
                {{-- Connecting line (desktop only) --}}
                <div class="hidden md:block absolute top-12 left-[16.67%] right-[16.67%] h-px bg-zinc-800" aria-hidden="true"></div>

                {{-- Step 1 --}}
                <div class="relative traiq-reveal">
                    <span class="font-['Bebas_Neue'] text-6xl text-brand-lime/20 leading-none">01</span>
                    <h3 class="mt-4 text-xl font-semibold text-white">Connect Claude</h3>
                    <p class="mt-3 text-zinc-400 leading-relaxed">
                        Add {{ config('app.name') }} as an MCP server in Claude. It takes less than 5 minutes.
                    </p>
                </div>

                {{-- Step 2 --}}
                <div class="relative traiq-reveal" style="transition-delay: 0.1s">
                    <span class="font-['Bebas_Neue'] text-6xl text-brand-lime/20 leading-none">02</span>
                    <h3 class="mt-4 text-xl font-semibold text-white">Tell Claude your goals</h3>
                    <p class="mt-3 text-zinc-400 leading-relaxed">
                        Describe your fitness goals, schedule, and limitations. Claude creates your personalized plan.
                    </p>
                </div>

                {{-- Step 3 --}}
                <div class="relative traiq-reveal" style="transition-delay: 0.2s">
                    <span class="font-['Bebas_Neue'] text-6xl text-brand-lime/20 leading-none">03</span>
                    <h3 class="mt-4 text-xl font-semibold text-white">Train & adapt</h3>
                    <p class="mt-3 text-zinc-400 leading-relaxed">
                        Track progress through Claude. Your plan evolves based on feedback and performance.
                    </p>
                </div>
            </div>

            <div class="mt-12 traiq-reveal">
                <a href="{{ route('get-started') }}" class="inline-flex items-center gap-2 text-brand-lime hover:text-brand-lime/80 font-medium transition-colors">
                    See full setup guide
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </a>
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

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                {{-- AI That Understands You --}}
                <div class="bg-white border-l-4 border-l-brand-lime rounded-lg p-8 traiq-reveal">
                    <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Claude-powered</span>
                    <h3 class="mt-3 text-2xl font-bold text-black leading-tight">AI that understands you</h3>
                    <p class="mt-4 text-zinc-600 leading-relaxed">
                        Unlike rigid apps, Claude understands context. Say "I'm exhausted today" and your workout adapts instantly.
                    </p>
                    <ul class="mt-6 pt-5 space-y-2.5 border-t border-zinc-100">
                        <li class="flex items-center gap-2 text-sm text-zinc-600">
                            <svg class="w-4 h-4 text-brand-lime flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            Natural language interactions
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-600">
                            <svg class="w-4 h-4 text-brand-lime flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            Remembers your full context
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-600">
                            <svg class="w-4 h-4 text-brand-lime flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            Explains every recommendation
                        </li>
                    </ul>
                </div>

                {{-- Adapts in Real-Time --}}
                <div class="bg-white border-l-4 border-l-brand-lime rounded-lg p-8 traiq-reveal" style="transition-delay: 0.1s">
                    <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Dynamic</span>
                    <h3 class="mt-3 text-2xl font-bold text-black leading-tight">Adapts in real-time</h3>
                    <p class="mt-4 text-zinc-600 leading-relaxed">
                        Miss a workout? Schedule change? Just tell Claude. Your plan adjusts instantly while keeping you on track.
                    </p>
                    <ul class="mt-6 pt-5 space-y-2.5 border-t border-zinc-100">
                        <li class="flex items-center gap-2 text-sm text-zinc-600">
                            <svg class="w-4 h-4 text-brand-lime flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            Instant plan adjustments
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-600">
                            <svg class="w-4 h-4 text-brand-lime flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            RPE-based intensity tuning
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-600">
                            <svg class="w-4 h-4 text-brand-lime flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            Learns from your feedback
                        </li>
                    </ul>
                </div>

                {{-- Free During Early Access --}}
                <div class="bg-white border-l-4 border-l-brand-lime rounded-lg p-8 traiq-reveal" style="transition-delay: 0.2s">
                    <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Early access</span>
                    <h3 class="mt-3 text-2xl font-bold text-black leading-tight">Free during beta</h3>
                    <p class="mt-4 text-zinc-600 leading-relaxed">
                        We're building {{ config('app.name') }} in public. Join early, get full access for free, and help shape the future of AI fitness.
                    </p>
                    <ul class="mt-6 pt-5 space-y-2.5 border-t border-zinc-100">
                        <li class="flex items-center gap-2 text-sm text-zinc-600">
                            <svg class="w-4 h-4 text-brand-lime flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            All features included
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-600">
                            <svg class="w-4 h-4 text-brand-lime flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            Bring your own Claude subscription
                        </li>
                        <li class="flex items-center gap-2 text-sm text-zinc-600">
                            <svg class="w-4 h-4 text-brand-lime flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            Help shape the product
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

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="border border-zinc-800 rounded-lg p-6 hover:border-brand-lime/30 transition-colors traiq-reveal">
                    <span class="text-xs font-semibold uppercase tracking-wide text-brand-lime">Recovery</span>
                    <h3 class="mt-3 text-xl font-bold text-white">Coming back from injury</h3>
                    <p class="mt-2 text-sm text-zinc-400">
                        Tell Claude about your injury and watch your plan adapt. Smart intensity adjustments and gradual progression built in.
                    </p>
                </div>

                <div class="border border-zinc-800 rounded-lg p-6 hover:border-brand-lime/30 transition-colors traiq-reveal" style="transition-delay: 0.1s">
                    <span class="text-xs font-semibold uppercase tracking-wide text-brand-lime">Beginners</span>
                    <h3 class="mt-3 text-xl font-bold text-white">Your first 5K</h3>
                    <p class="mt-2 text-sm text-zinc-400">
                        Say "I want to run a 5K in 3 months" and Claude builds a complete, beginner-friendly training plan.
                    </p>
                </div>

                <div class="border border-zinc-800 rounded-lg p-6 hover:border-brand-lime/30 transition-colors traiq-reveal" style="transition-delay: 0.2s">
                    <span class="text-xs font-semibold uppercase tracking-wide text-brand-lime">Busy schedule</span>
                    <h3 class="mt-3 text-xl font-bold text-white">Limited time, maximum results</h3>
                    <p class="mt-2 text-sm text-zinc-400">
                        Tell Claude "I only have 30 minutes on Tuesday and Thursday" and get optimized workouts that fit your life.
                    </p>
                </div>
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
                Join during early access and get started for free. Just bring your Claude subscription.
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
