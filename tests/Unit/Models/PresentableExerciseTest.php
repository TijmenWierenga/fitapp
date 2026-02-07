<?php

use App\Contracts\PresentableExercise;
use App\DataTransferObjects\Workout\ExercisePresentation;
use App\Models\CardioExercise;
use App\Models\DurationExercise;
use App\Models\StrengthExercise;

describe('StrengthExercise', function () {
    it('implements PresentableExercise', function () {
        expect(new StrengthExercise)->toBeInstanceOf(PresentableExercise::class);
    });

    it('returns an ExercisePresentation', function () {
        expect((new StrengthExercise)->present())->toBeInstanceOf(ExercisePresentation::class);
    });

    it('returns dot color and type label', function () {
        $presentation = (new StrengthExercise)->present();

        expect($presentation->dotColor)->toBe('bg-orange-400')
            ->and($presentation->typeLabel)->toBe('Strength');
    });

    describe('whatLines', function () {
        it('includes sets and reps', function () {
            $presentation = (new StrengthExercise([
                'target_sets' => 3,
                'target_reps_min' => 8,
                'target_reps_max' => 12,
            ]))->present();

            expect($presentation->whatLines)->toContain('3 sets of 8-12 reps');
        });

        it('includes weight', function () {
            $presentation = (new StrengthExercise(['target_weight' => 80]))->present();

            expect($presentation->whatLines)->toContain('at 80 kg');
        });

        it('includes tempo', function () {
            $presentation = (new StrengthExercise(['target_tempo' => '3-1-2-0']))->present();

            expect($presentation->whatLines)->toContain('tempo 3-1-2-0');
        });

        it('returns empty array when all fields are null', function () {
            expect((new StrengthExercise)->present()->whatLines)->toBe([]);
        });
    });

    describe('effortLines', function () {
        it('includes RPE', function () {
            $presentation = (new StrengthExercise(['target_rpe' => 7]))->present();

            expect($presentation->effortLines)->toContain('RPE 7 (Hard)');
        });

        it('returns empty array when RPE is null', function () {
            expect((new StrengthExercise)->present()->effortLines)->toBe([]);
        });
    });

    describe('restLines', function () {
        it('includes rest between sets', function () {
            $presentation = (new StrengthExercise(['rest_after' => 90]))->present();

            expect($presentation->restLines)->toContain('1min 30s between sets');
        });

        it('returns empty array when rest is null', function () {
            expect((new StrengthExercise)->present()->restLines)->toBe([]);
        });
    });
});

describe('CardioExercise', function () {
    it('implements PresentableExercise', function () {
        expect(new CardioExercise)->toBeInstanceOf(PresentableExercise::class);
    });

    it('returns dot color and type label', function () {
        $presentation = (new CardioExercise)->present();

        expect($presentation->dotColor)->toBe('bg-blue-400')
            ->and($presentation->typeLabel)->toBe('Cardio');
    });

    describe('whatLines', function () {
        it('includes duration', function () {
            $presentation = (new CardioExercise(['target_duration' => 1800]))->present();

            expect($presentation->whatLines)->toContain('30min');
        });

        it('includes distance', function () {
            $presentation = (new CardioExercise(['target_distance' => 5000]))->present();

            expect($presentation->whatLines)->toContain('5 km');
        });

        it('returns empty array when all fields are null', function () {
            expect((new CardioExercise)->present()->whatLines)->toBe([]);
        });
    });

    describe('effortLines', function () {
        it('includes pace range', function () {
            $presentation = (new CardioExercise([
                'target_pace_min' => 300,
                'target_pace_max' => 330,
            ]))->present();

            expect($presentation->effortLines[0])->toStartWith('Pace:');
        });

        it('includes heart rate zone', function () {
            $presentation = (new CardioExercise(['target_heart_rate_zone' => 3]))->present();

            expect($presentation->effortLines)->toContain('Zone 3');
        });

        it('includes heart rate range', function () {
            $presentation = (new CardioExercise([
                'target_heart_rate_min' => 140,
                'target_heart_rate_max' => 160,
            ]))->present();

            expect($presentation->effortLines)->toContain('140-160 bpm');
        });

        it('includes power', function () {
            $presentation = (new CardioExercise(['target_power' => 250]))->present();

            expect($presentation->effortLines)->toContain('250 W');
        });

        it('returns empty array when all fields are null', function () {
            expect((new CardioExercise)->present()->effortLines)->toBe([]);
        });
    });

    describe('restLines', function () {
        it('always returns empty array', function () {
            expect((new CardioExercise)->present()->restLines)->toBe([]);
        });
    });
});

describe('DurationExercise', function () {
    it('implements PresentableExercise', function () {
        expect(new DurationExercise)->toBeInstanceOf(PresentableExercise::class);
    });

    it('returns dot color and type label', function () {
        $presentation = (new DurationExercise)->present();

        expect($presentation->dotColor)->toBe('bg-emerald-400')
            ->and($presentation->typeLabel)->toBe('Duration');
    });

    describe('whatLines', function () {
        it('includes duration', function () {
            $presentation = (new DurationExercise(['target_duration' => 60]))->present();

            expect($presentation->whatLines)->toContain('1min');
        });

        it('returns empty array when duration is null', function () {
            expect((new DurationExercise)->present()->whatLines)->toBe([]);
        });
    });

    describe('effortLines', function () {
        it('includes RPE', function () {
            $presentation = (new DurationExercise(['target_rpe' => 5]))->present();

            expect($presentation->effortLines)->toContain('RPE 5 (Moderate)');
        });

        it('returns empty array when RPE is null', function () {
            expect((new DurationExercise)->present()->effortLines)->toBe([]);
        });
    });

    describe('restLines', function () {
        it('always returns empty array', function () {
            expect((new DurationExercise)->present()->restLines)->toBe([]);
        });
    });
});

describe('architecture', function () {
    it('requires all exercise models to implement PresentableExercise', function () {
        expect([StrengthExercise::class, CardioExercise::class, DurationExercise::class])
            ->each(fn ($class) => $class->toImplement(PresentableExercise::class));
    });
});
