<?php

namespace App\Providers;

use App\Models\CardioExercise;
use App\Models\DurationExercise;
use App\Models\StrengthExercise;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        Passport::authorizationView(fn ($parameters) => view('mcp.authorize', $parameters)); // @phpstan-ignore-line
        Date::use(CarbonImmutable::class);

        Relation::morphMap([
            'strength_exercise' => StrengthExercise::class,
            'cardio_exercise' => CardioExercise::class,
            'duration_exercise' => DurationExercise::class,
        ]);
    }
}
