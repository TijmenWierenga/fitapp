<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title ?? null])
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-zinc-950">
        {{-- Navigation --}}
        <nav class="sticky top-0 z-50 backdrop-blur-lg bg-white/90 dark:bg-zinc-950/80 border-b border-zinc-200/80 dark:border-zinc-800/50 shadow-sm dark:shadow-none">
            <div class="flex items-center justify-between px-6 py-4 max-w-5xl mx-auto">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <x-app-logo />
                </a>
                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-sm text-zinc-700 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white transition-colors">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm text-zinc-700 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white transition-colors">
                            Log in
                        </a>
                        <a href="{{ route('register') }}" class="text-sm traiq-cta-gradient text-white px-4 py-2 rounded-lg font-medium transition-all">
                            Get started
                        </a>
                    @endauth
                </div>
            </div>
        </nav>

        <main class="max-w-5xl mx-auto px-6 py-12">
            {{ $slot }}
        </main>

        @fluxScripts
    </body>
</html>
