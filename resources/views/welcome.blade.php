<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head', ['title' => 'FitApp — AI-Powered Workout Planning'])
</head>
<body class="bg-zinc-950 text-zinc-100 font-sans antialiased">

    {{-- Navigation --}}
    <nav class="flex items-center justify-between px-6 py-4 max-w-6xl mx-auto">
        <a href="{{ route('home') }}" class="text-xl font-semibold tracking-tight text-white">
            FitApp
        </a>
        <div class="flex items-center gap-4">
            <a href="{{ route('login') }}" class="text-sm text-zinc-400 hover:text-white transition-colors">
                Inloggen
            </a>
            <a href="{{ route('register') }}" class="text-sm bg-brand-red hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors">
                Registreren
            </a>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="max-w-4xl mx-auto px-6 pt-24 pb-32 text-center">
        <span class="inline-block text-xs font-medium uppercase tracking-widest text-brand-amber mb-6">
            AI-Powered Fitness
        </span>
        <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold tracking-tight text-white leading-tight">
            Jouw persoonlijke<br>
            <span class="text-brand-red">workout coach</span>
        </h1>
        <p class="mt-6 text-lg text-zinc-400 max-w-2xl mx-auto leading-relaxed">
            FitApp combineert slimme AI met bewezen trainingsmethoden om trainingsschema's op maat te maken die meegroeien met jouw voortgang.
        </p>
        <div class="mt-10">
            <a href="{{ route('register') }}" class="inline-block bg-brand-red hover:bg-red-600 text-white font-semibold px-8 py-3 rounded-lg text-lg transition-colors">
                Start gratis proefperiode
            </a>
        </div>
    </section>

    {{-- Features --}}
    <section class="max-w-6xl mx-auto px-6 pb-32">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            {{-- Workout Planning --}}
            <div class="bg-zinc-900 rounded-2xl p-8 border border-zinc-800">
                <div class="w-10 h-10 rounded-lg bg-brand-red/10 flex items-center justify-center mb-5">
                    <svg class="w-5 h-5 text-brand-red" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-white mb-2">Workout Planning</h3>
                <p class="text-sm text-zinc-400 leading-relaxed">
                    Ontvang gepersonaliseerde trainingsschema's die zijn afgestemd op jouw doelen, niveau en beschikbare tijd.
                </p>
            </div>

            {{-- Progress Tracking --}}
            <div class="bg-zinc-900 rounded-2xl p-8 border border-zinc-800">
                <div class="w-10 h-10 rounded-lg bg-brand-amber/10 flex items-center justify-center mb-5">
                    <svg class="w-5 h-5 text-brand-amber" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-white mb-2">Voortgang Bijhouden</h3>
                <p class="text-sm text-zinc-400 leading-relaxed">
                    Volg je progressie met duidelijke statistieken en zie hoe je sterker wordt over tijd.
                </p>
            </div>

            {{-- AI Coaching --}}
            <div class="bg-zinc-900 rounded-2xl p-8 border border-zinc-800">
                <div class="w-10 h-10 rounded-lg bg-brand-red/10 flex items-center justify-center mb-5">
                    <svg class="w-5 h-5 text-brand-red" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-white mb-2">AI Coaching</h3>
                <p class="text-sm text-zinc-400 leading-relaxed">
                    Krijg slimme suggesties en aanpassingen op basis van je prestaties en herstel.
                </p>
            </div>
        </div>
    </section>

    {{-- Use Cases / Stories --}}
    <section class="max-w-6xl mx-auto px-6 pb-32">
        <div class="text-center mb-12">
            <span class="text-xs font-medium uppercase tracking-widest text-brand-amber">
                Succesverhalen
            </span>
            <h2 class="mt-4 text-3xl font-bold text-white">
                Voor elke fitnessdoelstelling
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            {{-- Story 1: Injury Recovery --}}
            <div class="bg-zinc-900 rounded-2xl p-8 border border-zinc-800">
                <span class="text-xs font-medium uppercase tracking-wider text-brand-red">
                    Herstel
                </span>
                <h3 class="mt-3 text-lg font-semibold text-white">
                    Terug naar hardlopen na een blessure
                </h3>
                <p class="mt-2 text-sm text-zinc-500">
                    Marco, 34 — Recreatieve hardloper
                </p>
                <p class="mt-4 text-sm text-zinc-400 leading-relaxed">
                    Na een knieoperatie wilde Marco veilig terugkeren naar het hardlopen. Met FitApp's blessuretracking en slimme aanpassingen op basis van zijn RPE en herstelgevoel bouwde hij stap voor stap zijn conditie weer op.
                </p>
                <div class="mt-6 pt-6 border-t border-zinc-800">
                    <p class="text-sm text-zinc-300 italic">
                        "De AI hield rekening met mijn blessure en paste mijn schema automatisch aan wanneer ik aangaf dat mijn knie opspeelde."
                    </p>
                </div>
            </div>

            {{-- Story 2: First 5K --}}
            <div class="bg-zinc-900 rounded-2xl p-8 border border-zinc-800">
                <span class="text-xs font-medium uppercase tracking-wider text-brand-amber">
                    Beginners
                </span>
                <h3 class="mt-3 text-lg font-semibold text-white">
                    Van de bank naar de eerste 5K
                </h3>
                <p class="mt-2 text-sm text-zinc-500">
                    Lisa, 28 — Kantoormedewerker
                </p>
                <p class="mt-4 text-sm text-zinc-400 leading-relaxed">
                    Lisa had nog nooit hardgelopen maar droomde ervan om een 5K te finishen. FitApp creëerde een beginnersvriendelijk schema dat haar geleidelijk opbouwde, met duidelijke doelen en voortgangsinzichten.
                </p>
                <div class="mt-6 pt-6 border-t border-zinc-800">
                    <p class="text-sm text-zinc-300 italic">
                        "Ik vond het geweldig om mijn voortgang te zien. Na 8 weken liep ik mijn eerste 5K — iets wat ik nooit voor mogelijk had gehouden!"
                    </p>
                </div>
            </div>

            {{-- Story 3: Busy Professional --}}
            <div class="bg-zinc-900 rounded-2xl p-8 border border-zinc-800">
                <span class="text-xs font-medium uppercase tracking-wider text-brand-red">
                    Druk schema
                </span>
                <h3 class="mt-3 text-lg font-semibold text-white">
                    Fit blijven met een volle agenda
                </h3>
                <p class="mt-2 text-sm text-zinc-500">
                    Thomas, 42 — Manager
                </p>
                <p class="mt-4 text-sm text-zinc-400 leading-relaxed">
                    Als drukke manager heeft Thomas weinig tijd voor fitness. FitApp plant efficiënte workouts die passen binnen zijn beschikbare dagen en gewenste sessieduur, zodat hij fit blijft zonder zijn agenda te overbelasten.
                </p>
                <div class="mt-6 pt-6 border-t border-zinc-800">
                    <p class="text-sm text-zinc-300 italic">
                        "Met slechts 3 dagen per week en korte sessies van 30 minuten heb ik eindelijk een routine die ik kan volhouden."
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer CTA --}}
    <section class="border-t border-zinc-800 py-20">
        <div class="max-w-2xl mx-auto px-6 text-center">
            <h2 class="text-2xl sm:text-3xl font-bold text-white mb-4">
                Klaar om te beginnen?
            </h2>
            <p class="text-zinc-400 mb-8">
                Start vandaag nog met je gratis proefperiode en ontdek hoe FitApp je training naar een hoger niveau tilt.
            </p>
            <a href="{{ route('register') }}" class="inline-block bg-brand-red hover:bg-red-600 text-white font-semibold px-8 py-3 rounded-lg transition-colors">
                Start gratis proefperiode
            </a>
        </div>
    </section>

</body>
</html>
