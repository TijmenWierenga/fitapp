<?php

use App\Data\CreateWorkoutData;
use App\Data\UpdateWorkoutData;
use App\Enums\Workout\Activity;
use App\Models\User;
use App\Models\Workout;
use App\Services\Workout\WorkoutService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->service = new WorkoutService;
});

describe('create', function () {
    it('creates a workout for a user', function () {
        $user = User::factory()->create();

        $data = new CreateWorkoutData(
            name: 'Morning Run',
            activity: Activity::Run,
            scheduledAt: Carbon::parse('2026-02-01 08:00:00'),
            notes: 'Easy pace',
        );

        $workout = $this->service->create($user, $data);

        expect($workout)
            ->toBeInstanceOf(Workout::class)
            ->name->toBe('Morning Run')
            ->activity->toBe(Activity::Run)
            ->notes->toBe('Easy pace')
            ->user_id->toBe($user->id);
    });

    it('creates a workout without notes', function () {
        $user = User::factory()->create();

        $data = new CreateWorkoutData(
            name: 'Strength Training',
            activity: Activity::Strength,
            scheduledAt: Carbon::parse('2026-02-01 18:00:00'),
        );

        $workout = $this->service->create($user, $data);

        expect($workout->notes)->toBeNull();
    });
});

describe('update', function () {
    it('updates an editable workout', function () {
        $user = User::factory()->create();
        $workout = Workout::factory()->for($user)->upcoming()->create([
            'name' => 'Original Name',
        ]);

        $data = new UpdateWorkoutData(
            name: 'Updated Name',
        );

        $updated = $this->service->update($user, $workout, $data);

        expect($updated->name)->toBe('Updated Name');
    });

    it('throws authorization exception for completed workout', function () {
        $user = User::factory()->create();
        $workout = Workout::factory()->for($user)->completed()->create();

        $data = new UpdateWorkoutData(name: 'New Name');

        $this->service->update($user, $workout, $data);
    })->throws(AuthorizationException::class);

    it('throws authorization exception for another users workout', function () {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $workout = Workout::factory()->for($owner)->upcoming()->create();

        $data = new UpdateWorkoutData(name: 'New Name');

        $this->service->update($otherUser, $workout, $data);
    })->throws(AuthorizationException::class);
});

describe('delete', function () {
    it('deletes an upcoming workout', function () {
        $user = User::factory()->create();
        $workout = Workout::factory()->for($user)->upcoming()->create();

        $result = $this->service->delete($user, $workout);

        expect($result)->toBeTrue();
        expect(Workout::find($workout->id))->toBeNull();
    });

    it('throws authorization exception for completed workout', function () {
        $user = User::factory()->create();
        $workout = Workout::factory()->for($user)->completed()->create();

        $this->service->delete($user, $workout);
    })->throws(AuthorizationException::class);
});

describe('complete', function () {
    it('marks an incomplete workout as completed', function () {
        $user = User::factory()->create();
        $workout = Workout::factory()->for($user)->upcoming()->create();

        $completed = $this->service->complete($user, $workout, 7, 4);

        expect($completed)
            ->rpe->toBe(7)
            ->feeling->toBe(4)
            ->completed_at->not->toBeNull();
    });

    it('throws authorization exception for already completed workout', function () {
        $user = User::factory()->create();
        $workout = Workout::factory()->for($user)->completed()->create();

        $this->service->complete($user, $workout, 7, 4);
    })->throws(AuthorizationException::class);
});

describe('find', function () {
    it('finds a workout belonging to the user', function () {
        $user = User::factory()->create();
        $workout = Workout::factory()->for($user)->create();

        $found = $this->service->find($user, $workout->id);

        expect($found->id)->toBe($workout->id);
    });

    it('returns null for workout belonging to another user', function () {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $workout = Workout::factory()->for($owner)->create();

        $found = $this->service->find($otherUser, $workout->id);

        expect($found)->toBeNull();
    });

    it('returns null for non-existent workout', function () {
        $user = User::factory()->create();

        $found = $this->service->find($user, 99999);

        expect($found)->toBeNull();
    });
});

describe('list', function () {
    it('lists all workouts for a user', function () {
        $user = User::factory()->create();
        Workout::factory()->for($user)->count(5)->create();

        $workouts = $this->service->list($user);

        expect($workouts)->toHaveCount(5);
    });

    it('filters upcoming workouts', function () {
        $user = User::factory()->create();
        Workout::factory()->for($user)->upcoming()->count(3)->create();
        Workout::factory()->for($user)->completed()->count(2)->create();

        $workouts = $this->service->list($user, 'upcoming');

        expect($workouts)->toHaveCount(3);
    });

    it('filters completed workouts', function () {
        $user = User::factory()->create();
        Workout::factory()->for($user)->upcoming()->count(3)->create();
        Workout::factory()->for($user)->completed()->count(2)->create();

        $workouts = $this->service->list($user, 'completed');

        expect($workouts)->toHaveCount(2);
    });

    it('respects the limit parameter', function () {
        $user = User::factory()->create();
        Workout::factory()->for($user)->count(30)->create();

        $workouts = $this->service->list($user, 'all', 10);

        expect($workouts)->toHaveCount(10);
    });
});
