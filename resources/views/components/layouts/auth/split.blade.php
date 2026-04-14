<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-zinc-950 dark:to-zinc-900">
        <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div class="bg-muted relative hidden h-full flex-col justify-between p-10 text-white lg:flex dark:border-e dark:border-zinc-800">
                <div class="absolute inset-0 bg-zinc-900"></div>

                {{-- Logo --}}
                <a href="{{ route('home') }}" class="relative z-20 flex items-center text-lg font-medium" wire:navigate>
                    <span class="flex h-10 w-10 items-center justify-center rounded-md">
                        <x-app-logo-icon class="me-2 h-7 fill-current text-white" />
                    </span>
                    {{ config('app.name', 'Laravel') }}
                </a>

                {{-- Marketing content --}}
                <div class="relative z-20">
                    <p class="mb-4 text-sm font-medium tracking-wider text-lime-400 uppercase">{{ __('Sports Training Platform') }}</p>
                    <h1 class="text-4xl font-bold leading-tight tracking-tight text-white xl:text-5xl">
                        {{ __('Track every rep.') }}<br>
                        {{ __('Run every race.') }}
                    </h1>
                    <p class="mt-6 max-w-md text-lg text-zinc-400">
                        {{ __('Connect your training data, analyze your performance and reach your goals faster with AI-powered insights.') }}
                    </p>
                </div>

                {{-- Stats --}}
                <div class="relative z-20 flex gap-8">
                    <div>
                        <p class="text-2xl font-bold text-white">50K+</p>
                        <p class="text-sm text-zinc-400">{{ __('Athletes') }}</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-white">2M+</p>
                        <p class="text-sm text-zinc-400">{{ __('Workouts logged') }}</p>
                    </div>
                </div>
            </div>
            <div class="w-full lg:p-8">
                <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                    <a href="{{ route('home') }}" class="z-20 flex flex-col items-center gap-2 font-medium lg:hidden" wire:navigate>
                        <span class="flex h-9 w-9 items-center justify-center rounded-md">
                            <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
                        </span>

                        <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                    </a>
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
