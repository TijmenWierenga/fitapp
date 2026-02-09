<?php

use App\Livewire\GetStarted;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\FitnessProfile;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use App\Livewire\Workout\Builder as WorkoutBuilder;
use App\Livewire\Workout\Show as WorkoutShow;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('home');

Route::get('/get-started', GetStarted::class)->name('get-started');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('workouts/create', WorkoutBuilder::class)
    ->middleware(['auth', 'verified'])
    ->name('workouts.create');

Route::get('workouts/{workout}/edit', WorkoutBuilder::class)
    ->middleware(['auth', 'verified'])
    ->name('workouts.edit');

Route::get('workouts/{workout}', WorkoutShow::class)
    ->middleware(['auth', 'verified'])
    ->name('workouts.show');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');

    Route::get('settings/fitness-profile', FitnessProfile::class)->name('fitness-profile.edit');
});

if (app()->environment('local')) {
    Route::get('login/as/{user}', function (\App\Models\User $user) {
        auth()->login($user);
        return redirect()->route('dashboard');
    });
}
