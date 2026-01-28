<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head', ['title' => 'FitApp - Train smarter, not harder'])
</head>
<body class="antialiased bg-zinc-950 text-white">
    {{-- Navigation --}}
    <nav class="border-b border-zinc-800">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-brand-red">
                        <x-app-logo-icon class="size-5 fill-current text-white" />
                    </div>
                    <span class="text-xl font-semibold">FitApp</span>
                </div>
                
                @if (Route::has('login'))
                    <div class="flex items-center gap-4">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="text-sm text-zinc-400 hover:text-white transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-red focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950 rounded">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-sm text-zinc-400 hover:text-white transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-red focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950 rounded">
                                Login
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="px-4 py-2 bg-zinc-800 hover:bg-zinc-700 rounded-lg text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-red focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950">
                                    Register
                                </a>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>
        </div>
    </nav>

    {{-- Hero Section --}}
    <section class="py-20 lg:py-32">
        <div class="container mx-auto px-6">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-5xl lg:text-6xl font-bold mb-6 leading-tight">
                    Train smarter, <br class="hidden sm:inline">not harder
                </h1>
                <p class="text-xl text-zinc-400 mb-10 max-w-2xl mx-auto">
                    AI-powered workout planning that adapts to your goals, tracks your progress, and helps you achieve results faster.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
                    <a href="{{ route('register') }}" class="px-8 py-4 bg-brand-red hover:bg-red-700 rounded-lg text-lg font-semibold transition-colors inline-flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950">
                        Start gratis proefperiode
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>
                    <div class="flex items-center gap-2 text-sm text-zinc-500">
                        <svg class="w-5 h-5 text-brand-amber" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                        </svg>
                        <span>No credit card required</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Features Section --}}
    <section class="py-20 bg-zinc-900/50">
        <div class="container mx-auto px-6">
            <div class="max-w-6xl mx-auto">
                <div class="grid md:grid-cols-3 gap-12">
                    {{-- Feature 1: Workout Planning --}}
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-zinc-800 rounded-xl mb-6">
                            <svg class="w-8 h-8 text-brand-red" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-3">Smart Workout Planning</h3>
                        <p class="text-zinc-400">
                            Create personalized workout plans tailored to your fitness level and goals with AI assistance.
                        </p>
                    </div>

                    {{-- Feature 2: Progress Tracking --}}
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-zinc-800 rounded-xl mb-6">
                            <svg class="w-8 h-8 text-brand-amber" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-3">Progress Tracking</h3>
                        <p class="text-zinc-400">
                            Monitor your performance, track improvements, and visualize your fitness journey over time.
                        </p>
                    </div>

                    {{-- Feature 3: AI Coaching --}}
                    <div class="text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-zinc-800 rounded-xl mb-6">
                            <svg class="w-8 h-8 text-brand-red" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold mb-3">AI-Powered Coaching</h3>
                        <p class="text-zinc-400">
                            Get intelligent recommendations and adaptive training suggestions based on your performance data.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer CTA Section --}}
    <section class="py-20">
        <div class="container mx-auto px-6">
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-3xl lg:text-4xl font-bold mb-6">
                    Ready to transform your training?
                </h2>
                <p class="text-lg text-zinc-400 mb-8">
                    Join athletes who are already training smarter with FitApp.
                </p>
                <a href="{{ route('register') }}" class="px-8 py-4 bg-brand-red hover:bg-red-700 rounded-lg text-lg font-semibold transition-colors inline-flex items-center gap-2 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-zinc-950">
                    Start gratis proefperiode
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="border-t border-zinc-800 py-8">
        <div class="container mx-auto px-6">
            <div class="text-center text-sm text-zinc-500">
                <p>&copy; {{ date('Y') }} FitApp. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>
