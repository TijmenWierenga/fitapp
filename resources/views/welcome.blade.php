<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => config('app.name') . ' — Your Personal Workout Intelligence'])
</head>
<body class="bg-zinc-50 dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 font-sans antialiased overflow-x-hidden">

    {{-- Animated Background --}}
    <div class="fixed inset-0 traiq-bg-grid pointer-events-none" aria-hidden="true"></div>

    {{-- Gradient Orbs --}}
    <div class="fixed top-20 -left-40 w-96 h-96 bg-gradient-to-br from-brand-red/25 to-brand-amber/15 dark:from-brand-red/10 dark:to-brand-amber/5 rounded-full blur-3xl traiq-gradient-orb pointer-events-none" aria-hidden="true"></div>
    <div class="fixed top-1/2 -right-40 w-80 h-80 bg-gradient-to-br from-brand-amber/25 to-brand-red/15 dark:from-brand-amber/10 dark:to-brand-red/5 rounded-full blur-3xl traiq-gradient-orb-delayed pointer-events-none" aria-hidden="true"></div>

    {{-- Early Access Banner --}}
    <div class="bg-gradient-to-r from-brand-red/10 via-brand-amber/10 to-brand-red/10 dark:from-brand-red/5 dark:via-brand-amber/5 dark:to-brand-red/5 border-b border-brand-red/20 dark:border-brand-red/10">
        <div class="max-w-6xl mx-auto px-6 py-2.5 text-center">
            <p class="text-sm text-zinc-700 dark:text-zinc-300">
                <span class="font-semibold text-brand-red">Early Access:</span>
                Get started FREE while we're in beta
                <span class="mx-2 text-zinc-400">•</span>
                Bring your own Claude subscription
            </p>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="sticky top-0 z-50 backdrop-blur-lg bg-zinc-50/90 dark:bg-zinc-950/80 border-b border-zinc-200/80 dark:border-zinc-800/50 shadow-sm dark:shadow-none">
        <div class="flex items-center justify-between px-6 py-4 max-w-6xl mx-auto">
            <a href="{{ route('home') }}">
                <x-app-logo />
            </a>
            <div class="flex items-center gap-4">
                <a href="{{ route('login') }}" class="text-sm text-zinc-700 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white transition-colors">
                    Log in
                </a>
                <a href="{{ route('register') }}" class="text-sm traiq-cta-gradient text-white px-4 py-2 rounded-lg font-medium transition-all">
                    Get started free
                </a>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="relative max-w-4xl mx-auto px-6 pt-24 pb-32 text-center min-h-[90vh] flex flex-col justify-center">
        {{-- Hero gradient orb --}}
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[400px] bg-gradient-to-br from-brand-red/35 to-brand-amber/20 dark:from-brand-red/30 dark:to-brand-amber/20 rounded-full blur-3xl traiq-gradient-orb pointer-events-none" aria-hidden="true"></div>
        {{-- Second layered glow --}}
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[700px] h-[250px] bg-gradient-to-b from-brand-red/25 to-transparent dark:from-brand-red/20 dark:to-transparent rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>

        <div class="relative z-10">
            <span class="inline-block text-xs font-medium uppercase tracking-widest text-brand-amber mb-6 traiq-fade-in">
                AI-Powered Fitness
            </span>
            <h1 class="text-5xl md:text-7xl font-bold tracking-tight text-zinc-900 dark:text-white leading-tight traiq-fade-in-delay-1">
                Your personal<br>
                <span class="traiq-text-gradient">workout intelligence</span>
            </h1>
            <p class="mt-6 text-lg md:text-xl text-zinc-700 dark:text-zinc-400 max-w-2xl mx-auto leading-relaxed traiq-fade-in-delay-2">
                {{ config('app.name') }} connects Claude to your fitness journey. Get personalized, adaptive training plans through natural conversation.
            </p>
            <div class="mt-12 traiq-fade-in-delay-3 flex flex-col items-center gap-3">
                <a href="{{ route('register') }}" class="inline-block traiq-cta-gradient text-white font-semibold px-8 py-4 rounded-xl text-lg transition-all">
                    Get started free
                </a>
                <span class="text-sm text-zinc-500 dark:text-zinc-500">Free during early access. Just bring your Claude subscription.</span>
            </div>
        </div>

        {{-- Scroll indicator --}}
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 traiq-fade-in-delay-4">
            <span class="text-xs text-zinc-600 dark:text-zinc-400 tracking-wide font-medium">Discover more</span>
            <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400 animate-bounce" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
            </svg>
        </div>
    </section>

    {{-- How It Works --}}
    <section class="relative max-w-6xl mx-auto px-6 pb-32">
        <div class="text-center mb-16">
            <span class="text-xs font-medium uppercase tracking-widest text-brand-amber">
                Powered by Claude
            </span>
            <h2 class="mt-4 text-3xl md:text-4xl font-bold text-zinc-900 dark:text-white">
                How it works
            </h2>
            <p class="mt-3 text-zinc-600 dark:text-zinc-500 max-w-2xl mx-auto">
                {{ config('app.name') }} connects Claude AI directly to your workout data. Just talk naturally about your fitness goals, and Claude handles the rest.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            {{-- Step 1 --}}
            <div class="text-center">
                <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-brand-red/10 dark:bg-brand-red/20 flex items-center justify-center">
                    <span class="text-xl font-bold text-brand-red">1</span>
                </div>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">Connect Claude</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Add {{ config('app.name') }} as an MCP server in Claude Code. It takes less than 5 minutes.
                </p>
            </div>

            {{-- Step 2 --}}
            <div class="text-center">
                <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-brand-amber/10 dark:bg-brand-amber/20 flex items-center justify-center">
                    <span class="text-xl font-bold text-brand-amber">2</span>
                </div>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">Tell Claude your goals</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Describe your fitness goals, schedule, and any limitations. Claude creates your personalized plan.
                </p>
            </div>

            {{-- Step 3 --}}
            <div class="text-center">
                <div class="w-12 h-12 mx-auto mb-4 rounded-full bg-zinc-200/50 dark:bg-zinc-700/50 flex items-center justify-center">
                    <span class="text-xl font-bold text-zinc-600 dark:text-zinc-400">3</span>
                </div>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2">Train & adapt</h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">
                    Track your progress through Claude. Your plan evolves based on your feedback and performance.
                </p>
            </div>
        </div>

        <div class="mt-12 text-center">
            <a href="{{ route('get-started') }}" class="inline-flex items-center gap-2 text-brand-red hover:text-brand-red/80 font-medium transition-colors">
                See full setup guide
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </a>
        </div>
    </section>

    {{-- Features / USPs --}}
    <section class="relative max-w-6xl mx-auto px-6 pb-32">
        <div class="text-center mb-16">
            <span class="text-xs font-medium uppercase tracking-widest text-brand-amber">
                Why {{ config('app.name') }}
            </span>
            <h2 class="mt-4 text-3xl md:text-4xl font-bold text-zinc-900 dark:text-white">
                Training that works for you
            </h2>
            <p class="mt-3 text-zinc-600 dark:text-zinc-500">
                Stop guessing. Start progressing.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            {{-- AI That Understands You --}}
            <div class="border-l-2 border-l-brand-red border border-zinc-200/80 dark:border-zinc-800/30 rounded-lg bg-white/80 dark:bg-zinc-950 shadow-md dark:shadow-none pl-6 pr-8 py-8">
                <span class="text-xs font-semibold uppercase tracking-wide text-brand-red opacity-80">
                    Claude-powered
                </span>
                <h3 class="mt-3 text-2xl font-bold text-zinc-900 dark:text-white leading-tight">
                    AI that understands you
                </h3>
                <p class="mt-4 text-[0.9375rem] text-zinc-700 dark:text-zinc-400/90 leading-relaxed">
                    Unlike rigid apps, Claude understands context. Say "I'm exhausted today" and your workout adapts. Mention an injury, and Claude considers it for every future plan.
                </p>
                <ul class="mt-7 pt-6 space-y-3 border-t border-zinc-200/80 dark:border-zinc-800/30">
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-700 dark:text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-red flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Natural language interactions
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-700 dark:text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-red flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Remembers your full context
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-700 dark:text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-red flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Explains every recommendation
                    </li>
                </ul>
            </div>

            {{-- Adapts in Real-Time --}}
            <div class="border-l-2 border-l-brand-amber border border-zinc-200/80 dark:border-zinc-800/30 rounded-lg bg-white/80 dark:bg-zinc-950 shadow-md dark:shadow-none pl-6 pr-8 py-8">
                <span class="text-xs font-semibold uppercase tracking-wide text-brand-amber opacity-80">
                    Dynamic
                </span>
                <h3 class="mt-3 text-2xl font-bold text-zinc-900 dark:text-white leading-tight">
                    Adapts in real-time
                </h3>
                <p class="mt-4 text-[0.9375rem] text-zinc-700 dark:text-zinc-400/90 leading-relaxed">
                    Life happens. Miss a workout? Schedule change? Just tell Claude. Your plan adjusts instantly while keeping you on track toward your goals.
                </p>
                <ul class="mt-7 pt-6 space-y-3 border-t border-zinc-200/80 dark:border-zinc-800/30">
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-700 dark:text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-amber flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Instant plan adjustments
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-700 dark:text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-amber flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        RPE-based intensity tuning
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-700 dark:text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-amber flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Learns from your feedback
                    </li>
                </ul>
            </div>

            {{-- Free During Early Access --}}
            <div class="border-l-2 border-l-green-500 dark:border-l-green-600 border border-zinc-200/80 dark:border-zinc-800/30 rounded-lg bg-white/80 dark:bg-zinc-950 shadow-md dark:shadow-none pl-6 pr-8 py-8">
                <span class="text-xs font-semibold uppercase tracking-wide text-green-600 dark:text-green-500 opacity-80">
                    Early access
                </span>
                <h3 class="mt-3 text-2xl font-bold text-zinc-900 dark:text-white leading-tight">
                    Free during beta
                </h3>
                <p class="mt-4 text-[0.9375rem] text-zinc-700 dark:text-zinc-400/90 leading-relaxed">
                    We're building {{ config('app.name') }} in public. Join early, get full access for free, and help shape the future of AI-powered fitness.
                </p>
                <ul class="mt-7 pt-6 space-y-3 border-t border-zinc-200/80 dark:border-zinc-800/30">
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-700 dark:text-zinc-400/80">
                        <svg class="w-4 h-4 text-green-600 dark:text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        All features included
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-700 dark:text-zinc-400/80">
                        <svg class="w-4 h-4 text-green-600 dark:text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Bring your own Claude subscription
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-700 dark:text-zinc-400/80">
                        <svg class="w-4 h-4 text-green-600 dark:text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Help shape the product
                    </li>
                </ul>
            </div>
        </div>
    </section>

    {{-- Use Cases --}}
    <section class="relative max-w-6xl mx-auto px-6 pb-32">
        <div class="text-center mb-16">
            <span class="text-xs font-medium uppercase tracking-widest text-brand-amber">
                Perfect for
            </span>
            <h2 class="mt-4 text-3xl md:text-4xl font-bold text-zinc-900 dark:text-white">
                For every fitness goal
            </h2>
            <p class="mt-3 text-zinc-600 dark:text-zinc-500">
                Discover how {{ config('app.name') }} can help you with personalized AI coaching.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            {{-- Injury Recovery --}}
            <div class="traiq-use-case-card px-8 py-8">
                <span class="text-xs font-semibold uppercase tracking-wide text-brand-red">
                    Recovery
                </span>
                <h3 class="mt-3 text-2xl font-bold text-zinc-900 dark:text-white leading-tight">
                    Coming back from injury
                </h3>
                <p class="mt-2 text-xs text-zinc-600 dark:text-zinc-500 italic">
                    Perfect for: athletes in recovery
                </p>
                <p class="mt-4 text-[0.9375rem] text-zinc-700 dark:text-zinc-400/90 leading-relaxed">
                    Tell Claude about your injury and it automatically adjusts every workout. Say "my knee hurts today" and watch your plan adapt instantly.
                </p>
                <ul class="mt-7 pt-6 space-y-3 border-t border-zinc-200/80 dark:border-zinc-800/30">
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-700 dark:text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-red flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Built-in injury tracking
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-700 dark:text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-red flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Smart intensity adjustments
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-700 dark:text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-red flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Gradual progression
                    </li>
                </ul>
            </div>

            {{-- Goal Achievement --}}
            <div class="traiq-use-case-card px-8 py-8">
                <span class="text-xs font-semibold uppercase tracking-wide text-brand-amber">
                    Beginners
                </span>
                <h3 class="mt-3 text-2xl font-bold text-zinc-900 dark:text-white leading-tight">
                    Your first 5K
                </h3>
                <p class="mt-2 text-xs text-zinc-600 dark:text-zinc-500 italic">
                    Perfect for: new athletes
                </p>
                <p class="mt-4 text-[0.9375rem] text-zinc-700 dark:text-zinc-400/90 leading-relaxed">
                    Just say "I want to run a 5K in 3 months but I've never run before." Claude builds a complete, beginner-friendly training plan.
                </p>
                <ul class="mt-7 pt-6 space-y-3 border-t border-zinc-200/80 dark:border-zinc-800/30">
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-700 dark:text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-amber flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Step-by-step progression
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-700 dark:text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-amber flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Clear progress insights
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-700 dark:text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-amber flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Achievable goals
                    </li>
                </ul>
            </div>

            {{-- Busy Lifestyle --}}
            <div class="traiq-use-case-card px-8 py-8">
                <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                    Busy schedule
                </span>
                <h3 class="mt-3 text-2xl font-bold text-zinc-900 dark:text-white leading-tight">
                    Limited time, maximum results
                </h3>
                <p class="mt-2 text-xs text-zinc-600 dark:text-zinc-500 italic">
                    Perfect for: busy professionals
                </p>
                <p class="mt-4 text-[0.9375rem] text-zinc-700 dark:text-zinc-400/90 leading-relaxed">
                    Tell Claude "I only have 30 minutes on Tuesday and Thursday" and get optimized workouts that fit your real schedule.
                </p>
                <ul class="mt-7 pt-6 space-y-3 border-t border-zinc-200/80 dark:border-zinc-800/30">
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-700 dark:text-zinc-400/80">
                        <svg class="w-4 h-4 text-zinc-600 dark:text-zinc-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Flexible scheduling
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-700 dark:text-zinc-400/80">
                        <svg class="w-4 h-4 text-zinc-600 dark:text-zinc-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Short, effective sessions
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-700 dark:text-zinc-400/80">
                        <svg class="w-4 h-4 text-zinc-600 dark:text-zinc-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Fits your availability
                    </li>
                </ul>
            </div>
        </div>
    </section>

    {{-- Footer CTA --}}
    <section class="relative border-t border-zinc-200/80 dark:border-zinc-800 py-20">
        {{-- Bottom gradient orb --}}
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[500px] h-[300px] bg-gradient-to-t from-brand-red/25 to-transparent dark:from-brand-red/10 dark:to-transparent rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>

        <div class="relative z-10 max-w-2xl mx-auto px-6 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-white mb-4">
                Ready to train smarter?
            </h2>
            <p class="text-zinc-700 dark:text-zinc-400 text-lg mb-8">
                Join during early access and get started for free. Just bring your Claude subscription.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('register') }}" class="inline-block traiq-cta-gradient text-white font-semibold px-8 py-4 rounded-xl text-lg transition-all">
                    Get started free
                </a>
                <a href="{{ route('get-started') }}" class="inline-flex items-center gap-2 text-zinc-700 dark:text-zinc-300 hover:text-brand-red dark:hover:text-brand-red font-medium transition-colors">
                    View setup guide
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            </div>
        </div>
    </section>

</body>
</html>
