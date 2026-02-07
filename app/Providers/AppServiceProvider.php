<?php

namespace App\Providers;

use App\Models\ExerciseGroup;
use App\Models\IntervalBlock;
use App\Models\NoteBlock;
use App\Models\RestBlock;
use Carbon\CarbonImmutable;
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
        Passport::authorizationView(fn ($parameters) => view('mcp.authorize', $parameters)); // @phpstan-ignore-line
        Date::use(CarbonImmutable::class);

        Relation::enforceMorphMap([
            'interval_block' => IntervalBlock::class,
            'exercise_group' => ExerciseGroup::class,
            'rest_block' => RestBlock::class,
            'note_block' => NoteBlock::class,
        ]);
    }
}
