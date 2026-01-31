<?php

use App\Models\User;
use App\Models\Workout;
use App\Policies\WorkoutPolicy;

beforeEach(function () {
    $this->policy = new WorkoutPolicy;
});

describe('view', function () {
    it('allows owner to view their workout', function () {
        $user = User::factory()->create();
        $workout = Workout::factory()->for($user)->create();

        expect($this->policy->view($user, $workout))->toBeTrue();
    });

    it('denies other users from viewing workout', function () {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $workout = Workout::factory()->for($owner)->create();

        expect($this->policy->view($otherUser, $workout))->toBeFalse();
    });
});

describe('update', function () {
    it('allows owner to update their editable workout', function () {
        $user = User::factory()->create();
        $workout = Workout::factory()->for($user)->upcoming()->create();

        expect($this->policy->update($user, $workout))->toBeTrue();
    });

    it('denies update for completed workout', function () {
        $user = User::factory()->create();
        $workout = Workout::factory()->for($user)->completed()->create();

        expect($this->policy->update($user, $workout))->toBeFalse();
    });

    it('denies other users from updating workout', function () {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $workout = Workout::factory()->for($owner)->upcoming()->create();

        expect($this->policy->update($otherUser, $workout))->toBeFalse();
    });
});

describe('delete', function () {
    it('allows owner to delete their deletable workout', function () {
        $user = User::factory()->create();
        $workout = Workout::factory()->for($user)->upcoming()->create();

        expect($this->policy->delete($user, $workout))->toBeTrue();
    });

    it('denies delete for completed workout', function () {
        $user = User::factory()->create();
        $workout = Workout::factory()->for($user)->completed()->create();

        expect($this->policy->delete($user, $workout))->toBeFalse();
    });

    it('denies other users from deleting workout', function () {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $workout = Workout::factory()->for($owner)->upcoming()->create();

        expect($this->policy->delete($otherUser, $workout))->toBeFalse();
    });
});

describe('complete', function () {
    it('allows owner to complete their incomplete workout', function () {
        $user = User::factory()->create();
        $workout = Workout::factory()->for($user)->upcoming()->create();

        expect($this->policy->complete($user, $workout))->toBeTrue();
    });

    it('denies completing already completed workout', function () {
        $user = User::factory()->create();
        $workout = Workout::factory()->for($user)->completed()->create();

        expect($this->policy->complete($user, $workout))->toBeFalse();
    });

    it('denies other users from completing workout', function () {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $workout = Workout::factory()->for($owner)->upcoming()->create();

        expect($this->policy->complete($otherUser, $workout))->toBeFalse();
    });
});
