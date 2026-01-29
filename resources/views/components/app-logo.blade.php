<div class="flex items-center gap-2">
    <svg class="h-8 w-8" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="traiq-logo-gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:#dc2626" />
                <stop offset="100%" style="stop-color:#f59e0b" />
            </linearGradient>
        </defs>
        <rect width="48" height="48" rx="12" fill="#18181b"/>
        {{-- Running track oval --}}
        <ellipse cx="24" cy="24" rx="18" ry="13" stroke="url(#traiq-logo-gradient)" stroke-width="2.5" fill="none"/>
        {{-- Inner lane --}}
        <ellipse cx="24" cy="24" rx="13.5" ry="9" stroke="url(#traiq-logo-gradient)" stroke-width="1.5" fill="none" opacity="0.4"/>
        {{-- Start/finish line --}}
        <line x1="24" y1="11" x2="24" y2="37" stroke="url(#traiq-logo-gradient)" stroke-width="3" stroke-linecap="round"/>
        {{-- Finish line details (checkered flag effect) --}}
        <rect x="22" y="13" width="2" height="2" fill="#18181b"/>
        <rect x="24" y="13" width="2" height="2" fill="url(#traiq-logo-gradient)"/>
        <rect x="24" y="15" width="2" height="2" fill="#18181b"/>
        <rect x="22" y="15" width="2" height="2" fill="url(#traiq-logo-gradient)"/>
        <rect x="22" y="31" width="2" height="2" fill="#18181b"/>
        <rect x="24" y="31" width="2" height="2" fill="url(#traiq-logo-gradient)"/>
        <rect x="24" y="33" width="2" height="2" fill="#18181b"/>
        <rect x="22" y="33" width="2" height="2" fill="url(#traiq-logo-gradient)"/>
    </svg>
    <span class="text-xl font-bold tracking-tight text-zinc-900 dark:text-white">{{ config('app.name') }}</span>
</div>
