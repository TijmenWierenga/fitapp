<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head', ['title' => config('app.name') . ' — Your Personal Workout Intelligence'])
</head>
<body class="bg-zinc-950 text-zinc-100 font-sans antialiased overflow-x-hidden">

    {{-- Animated Background --}}
    <div class="fixed inset-0 traiq-bg-grid pointer-events-none" aria-hidden="true"></div>

    {{-- Gradient Orbs --}}
    <div class="fixed top-20 -left-40 w-96 h-96 bg-gradient-to-br from-brand-red/10 to-brand-amber/5 rounded-full blur-3xl traiq-gradient-orb pointer-events-none" aria-hidden="true"></div>
    <div class="fixed top-1/2 -right-40 w-80 h-80 bg-gradient-to-br from-brand-amber/10 to-brand-red/5 rounded-full blur-3xl traiq-gradient-orb-delayed pointer-events-none" aria-hidden="true"></div>

    {{-- Navigation --}}
    <nav class="sticky top-0 z-50 backdrop-blur-lg bg-zinc-950/80 border-b border-zinc-800/50">
        <div class="flex items-center justify-between px-6 py-4 max-w-6xl mx-auto">
            <a href="{{ route('home') }}" class="flex items-center gap-2">
                <svg class="h-8 w-8" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <linearGradient id="nav-logo-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#dc2626" />
                            <stop offset="100%" style="stop-color:#f59e0b" />
                        </linearGradient>
                    </defs>
                    <rect width="48" height="48" rx="12" fill="#18181b"/>
                    <circle cx="10" cy="14" r="3" fill="url(#nav-logo-gradient)" class="traiq-node"/>
                    <line x1="13" y1="14" x2="21" y2="14" stroke="url(#nav-logo-gradient)" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="24" cy="14" r="3" fill="url(#nav-logo-gradient)" class="traiq-node"/>
                    <line x1="27" y1="14" x2="35" y2="14" stroke="url(#nav-logo-gradient)" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="38" cy="14" r="3" fill="url(#nav-logo-gradient)" class="traiq-node"/>
                    <line x1="24" y1="17" x2="24" y2="21" stroke="url(#nav-logo-gradient)" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="24" cy="24" r="3" fill="url(#nav-logo-gradient)" class="traiq-node"/>
                    <line x1="26" y1="26" x2="30" y2="30" stroke="url(#nav-logo-gradient)" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="32" cy="32" r="3" fill="url(#nav-logo-gradient)" class="traiq-node"/>
                    <line x1="34" y1="34" x2="38" y2="38" stroke="url(#nav-logo-gradient)" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="40" cy="40" r="3" fill="url(#nav-logo-gradient)" class="traiq-node"/>
                </svg>
                <span class="text-xl font-bold tracking-tight text-white">{{ config('app.name') }}</span>
            </a>
            <div class="flex items-center gap-4">
                <a href="{{ route('login') }}" class="text-sm text-zinc-400 hover:text-white transition-colors">
                    Log in
                </a>
                <a href="{{ route('register') }}" class="text-sm traiq-cta-gradient text-white px-4 py-2 rounded-lg font-medium transition-all">
                    Get started
                </a>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="relative max-w-4xl mx-auto px-6 pt-24 pb-32 text-center min-h-[90vh] flex flex-col justify-center">
        {{-- Hero gradient orb --}}
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[400px] bg-gradient-to-br from-brand-red/30 to-brand-amber/20 rounded-full blur-3xl traiq-gradient-orb pointer-events-none" aria-hidden="true"></div>
        {{-- Second layered glow --}}
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[700px] h-[250px] bg-gradient-to-b from-brand-red/20 to-transparent rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>

        <div class="relative z-10">
            <span class="inline-block text-xs font-medium uppercase tracking-widest text-brand-amber mb-6 traiq-fade-in">
                AI-Powered Fitness
            </span>
            <h1 class="text-5xl md:text-7xl font-bold tracking-tight text-white leading-tight traiq-fade-in-delay-1">
                Your personal<br>
                <span class="traiq-text-gradient">workout intelligence</span>
            </h1>
            <p class="mt-6 text-lg md:text-xl text-zinc-400 max-w-2xl mx-auto leading-relaxed traiq-fade-in-delay-2">
                {{ config('app.name') }} uses AI to create adaptive training plans that evolve with your progress, recovery, and lifestyle.
            </p>
            <div class="mt-12 traiq-fade-in-delay-3">
                <a href="{{ route('register') }}" class="inline-block traiq-cta-gradient text-white font-semibold px-8 py-4 rounded-xl text-lg transition-all">
                    Start free trial
                </a>
            </div>
        </div>

        {{-- Scroll indicator --}}
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 traiq-fade-in-delay-4">
            <span class="text-xs text-zinc-400 tracking-wide font-medium">Discover more</span>
            <svg class="w-5 h-5 text-zinc-400 animate-bounce" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
            </svg>
        </div>
    </section>

    {{-- Features / USPs --}}
    <section class="relative max-w-6xl mx-auto px-6 pb-32">
        <div class="text-center mb-16">
            <span class="text-xs font-medium uppercase tracking-widest text-brand-amber">
                Why {{ config('app.name') }}
            </span>
            <h2 class="mt-4 text-3xl md:text-4xl font-bold text-white">
                Training that works for you
            </h2>
            <p class="mt-3 text-zinc-500">
                Stop guessing. Start progressing.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            {{-- Smart Planning --}}
            <div class="border-l-2 border-l-brand-red border border-zinc-800/30 rounded-lg bg-zinc-950 pl-6 pr-8 py-8">
                <span class="text-xs font-semibold uppercase tracking-wide text-brand-red opacity-80">
                    Save time
                </span>
                <h3 class="mt-3 text-2xl font-bold text-white leading-tight">
                    Your schedule, optimized
                </h3>
                <p class="mt-4 text-[0.9375rem] text-zinc-400/90 leading-relaxed">
                    No more wondering what to do next. Get a personalized workout plan that fits your available days and adapts when life gets in the way.
                </p>
                <ul class="mt-7 pt-6 space-y-3 border-t border-zinc-800/30">
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-red flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Workouts planned around your life
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-red flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Automatically reschedules missed sessions
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-red flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Never skip a beat
                    </li>
                </ul>
            </div>

            {{-- Progress Insights --}}
            <div class="border-l-2 border-l-brand-amber border border-zinc-800/30 rounded-lg bg-zinc-950 pl-6 pr-8 py-8">
                <span class="text-xs font-semibold uppercase tracking-wide text-brand-amber opacity-80">
                    Stay motivated
                </span>
                <h3 class="mt-3 text-2xl font-bold text-white leading-tight">
                    See your progress clearly
                </h3>
                <p class="mt-4 text-[0.9375rem] text-zinc-400/90 leading-relaxed">
                    Know exactly how far you've come. Track your workouts, monitor trends, and celebrate milestones that keep you moving forward.
                </p>
                <ul class="mt-7 pt-6 space-y-3 border-t border-zinc-800/30">
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-amber flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Visual progress tracking
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-amber flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Workout streaks and completion rates
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-amber flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        RPE and recovery trends
                    </li>
                </ul>
            </div>

            {{-- Adaptive Coaching --}}
            <div class="border-l-2 border-l-zinc-600 border border-zinc-800/30 rounded-lg bg-zinc-950 pl-6 pr-8 py-8">
                <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500 opacity-80">
                    Train smarter
                </span>
                <h3 class="mt-3 text-2xl font-bold text-white leading-tight">
                    A coach that listens
                </h3>
                <p class="mt-4 text-[0.9375rem] text-zinc-400/90 leading-relaxed">
                    Feeling tired? Had a tough week? Your plan adjusts based on how you actually feel, not just what's on the calendar.
                </p>
                <ul class="mt-7 pt-6 space-y-3 border-t border-zinc-800/30">
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-400/80">
                        <svg class="w-4 h-4 text-zinc-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Adjusts to your energy levels
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-400/80">
                        <svg class="w-4 h-4 text-zinc-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Prevents overtraining
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-400/80">
                        <svg class="w-4 h-4 text-zinc-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Learns from your feedback
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
            <h2 class="mt-4 text-3xl md:text-4xl font-bold text-white">
                For every fitness goal
            </h2>
            <p class="mt-3 text-zinc-500">
                Discover how {{ config('app.name') }} can help you with personalized AI coaching.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            {{-- Injury Recovery --}}
            <div class="traiq-use-case-card px-8 py-8">
                <span class="text-xs font-semibold uppercase tracking-wide text-brand-red">
                    Recovery
                </span>
                <h3 class="mt-3 text-2xl font-bold text-white leading-tight">
                    Coming back from injury
                </h3>
                <p class="mt-2 text-xs text-zinc-500 italic">
                    Perfect for: athletes in recovery
                </p>
                <p class="mt-4 text-[0.9375rem] text-zinc-400/90 leading-relaxed">
                    Want to safely return to sport after an injury? {{ config('app.name') }} takes your injury history into account and automatically adjusts your schedule based on your RPE and recovery feeling.
                </p>
                <ul class="mt-7 pt-6 space-y-3 border-t border-zinc-800/30">
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-red flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Built-in injury tracking
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-red flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Smart intensity adjustments
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-400/80">
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
                <h3 class="mt-3 text-2xl font-bold text-white leading-tight">
                    Your first 5K
                </h3>
                <p class="mt-2 text-xs text-zinc-500 italic">
                    Perfect for: new athletes
                </p>
                <p class="mt-4 text-[0.9375rem] text-zinc-400/90 leading-relaxed">
                    Never exercised but want to start? {{ config('app.name') }} creates a beginner-friendly schedule that builds you up gradually, with clear goals and progress insights.
                </p>
                <ul class="mt-7 pt-6 space-y-3 border-t border-zinc-800/30">
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-amber flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Step-by-step progression
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-amber flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Clear progress insights
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-400/80">
                        <svg class="w-4 h-4 text-brand-amber flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Achievable goals
                    </li>
                </ul>
            </div>

            {{-- Busy Lifestyle --}}
            <div class="traiq-use-case-card px-8 py-8">
                <span class="text-xs font-semibold uppercase tracking-wide text-zinc-400">
                    Busy schedule
                </span>
                <h3 class="mt-3 text-2xl font-bold text-white leading-tight">
                    Limited time, maximum results
                </h3>
                <p class="mt-2 text-xs text-zinc-500 italic">
                    Perfect for: busy professionals
                </p>
                <p class="mt-4 text-[0.9375rem] text-zinc-400/90 leading-relaxed">
                    Limited time for fitness? {{ config('app.name') }} plans efficient workouts that fit around your available days and preferred session duration, so you stay fit without overloading your schedule.
                </p>
                <ul class="mt-7 pt-6 space-y-3 border-t border-zinc-800/30">
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-400/80">
                        <svg class="w-4 h-4 text-zinc-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Flexible scheduling
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-400/80">
                        <svg class="w-4 h-4 text-zinc-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Short, effective sessions
                    </li>
                    <li class="flex items-center gap-2 text-[0.8125rem] text-zinc-400/80">
                        <svg class="w-4 h-4 text-zinc-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Fits your availability
                    </li>
                </ul>
            </div>
        </div>
    </section>

    {{-- Footer CTA --}}
    <section class="relative border-t border-zinc-800 py-20">
        {{-- Bottom gradient orb --}}
        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[500px] h-[300px] bg-gradient-to-t from-brand-red/10 to-transparent rounded-full blur-3xl pointer-events-none" aria-hidden="true"></div>

        <div class="relative z-10 max-w-2xl mx-auto px-6 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-4">
                Ready to train smarter?
            </h2>
            <p class="text-zinc-400 text-lg mb-8">
                Start your free trial today and discover how {{ config('app.name') }} can transform your training.
            </p>
            <a href="{{ route('register') }}" class="inline-block traiq-cta-gradient text-white font-semibold px-8 py-4 rounded-xl text-lg transition-all">
                Start free trial
            </a>
        </div>
    </section>

</body>
</html>
