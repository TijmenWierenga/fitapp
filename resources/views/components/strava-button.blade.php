@props(['intent' => 'login'])

<a
    href="{{ route('auth.strava.redirect', ['intent' => $intent]) }}"
    {{ $attributes->merge(['class' => 'inline-flex items-center justify-center gap-2 rounded-lg bg-[#FC4C02] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#E34402] focus:outline-none focus:ring-2 focus:ring-[#FC4C02] focus:ring-offset-2 w-full']) }}
>
    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
        <path d="M15.387 17.944l-2.089-4.116h-3.065L15.387 24l5.15-10.172h-3.066m-7.008-5.599l2.836 5.598h4.172L10.463 0l-7 13.828h4.169"/>
    </svg>
    {{ __('Continue with Strava') }}
</a>
