<?php

use App\Livewire\Chat\Coach;
use App\Livewire\Exercise\Show as ExerciseShow;
use App\Livewire\GetStarted;
use App\Livewire\Injury\Impact as InjuryImpact;
use App\Livewire\Injury\Index as InjuryIndex;
use App\Livewire\Injury\Reports as InjuryReports;
use App\Livewire\Injury\Show as InjuryShow;
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

Route::get('workouts/{workout}/export-fit', \App\Http\Controllers\ExportWorkoutFitController::class)
    ->middleware(['auth', 'verified'])
    ->name('workouts.export-fit');

Route::middleware(['auth'])->group(function () {
    Route::get('coach', Coach::class)->name('coach');
    Route::get('coach/{conversation}', Coach::class)->name('coach.conversation');

    Route::get('exercises/{exercise}', ExerciseShow::class)->name('exercises.show');

    Route::redirect('workload-guide', '/docs/workload-guide');

    Route::view('docs', 'docs.index')->name('docs.index');
    Route::view('docs/workload-guide', 'docs.workload-guide')->name('docs.workload-guide');
    Route::view('docs/garmin-export', 'docs.garmin-export')->name('docs.garmin-export');

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

    Route::get('injuries', InjuryIndex::class)->name('injuries.index');
    Route::get('injuries/impact', InjuryImpact::class)->name('injuries.impact');
    Route::get('injuries/{injury}', InjuryShow::class)->name('injuries.show');
    Route::get('injuries/{injury}/reports', InjuryReports::class)->name('injuries.reports');
});

if (app()->environment('local')) {
    Route::get('login/as/{user}', function (\App\Models\User $user) {
        auth()->login($user);

        return redirect()->route('dashboard');
    });
}
