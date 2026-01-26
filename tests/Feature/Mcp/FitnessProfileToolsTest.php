<?php

use App\Enums\BodyPart;
use App\Enums\FitnessGoal;
use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\AddInjuryTool;
use App\Mcp\Tools\RemoveInjuryTool;
use App\Mcp\Tools\UpdateFitnessProfileTool;
use App\Models\FitnessProfile;
use App\Models\Injury;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

describe('UpdateFitnessProfileTool', function () {
    it('creates a fitness profile successfully', function () {
        $user = User::factory()->create();

        $response = WorkoutServer::tool(UpdateFitnessProfileTool::class, [
            'user_id' => $user->id,
            'primary_goal' => 'weight_loss',
            'goal_details' => 'Lose 15kg by summer',
            'available_days_per_week' => 4,
            'minutes_per_session' => 60,
        ]);

        $response->assertOk()
            ->assertSee('weight_loss')
            ->assertSee('Fitness profile updated successfully');

        assertDatabaseHas('fitness_profiles', [
            'user_id' => $user->id,
            'primary_goal' => 'weight_loss',
            'goal_details' => 'Lose 15kg by summer',
            'available_days_per_week' => 4,
            'minutes_per_session' => 60,
        ]);
    });

    it('updates an existing fitness profile', function () {
        $user = User::factory()->create();
        FitnessProfile::factory()->create([
            'user_id' => $user->id,
            'primary_goal' => FitnessGoal::WeightLoss,
        ]);

        $response = WorkoutServer::tool(UpdateFitnessProfileTool::class, [
            'user_id' => $user->id,
            'primary_goal' => 'muscle_gain',
            'available_days_per_week' => 6,
            'minutes_per_session' => 90,
        ]);

        $response->assertOk()
            ->assertSee('muscle_gain');

        assertDatabaseHas('fitness_profiles', [
            'user_id' => $user->id,
            'primary_goal' => 'muscle_gain',
            'available_days_per_week' => 6,
        ]);
    });

    it('fails with invalid user_id', function () {
        $response = WorkoutServer::tool(UpdateFitnessProfileTool::class, [
            'user_id' => 99999,
            'primary_goal' => 'weight_loss',
            'available_days_per_week' => 4,
            'minutes_per_session' => 60,
        ]);

        $response->assertHasErrors()
            ->assertSee('User not found');
    });

    it('fails with invalid primary_goal', function () {
        $user = User::factory()->create();

        $response = WorkoutServer::tool(UpdateFitnessProfileTool::class, [
            'user_id' => $user->id,
            'primary_goal' => 'invalid_goal',
            'available_days_per_week' => 4,
            'minutes_per_session' => 60,
        ]);

        $response->assertHasErrors();
    });

    it('fails with days out of range', function (int $days) {
        $user = User::factory()->create();

        $response = WorkoutServer::tool(UpdateFitnessProfileTool::class, [
            'user_id' => $user->id,
            'primary_goal' => 'weight_loss',
            'available_days_per_week' => $days,
            'minutes_per_session' => 60,
        ]);

        $response->assertHasErrors();
    })->with([0, 8, -1, 100]);

    it('fails with minutes out of range', function (int $minutes) {
        $user = User::factory()->create();

        $response = WorkoutServer::tool(UpdateFitnessProfileTool::class, [
            'user_id' => $user->id,
            'primary_goal' => 'weight_loss',
            'available_days_per_week' => 4,
            'minutes_per_session' => $minutes,
        ]);

        $response->assertHasErrors();
    })->with([10, 14, 181, 500]);
});

describe('AddInjuryTool', function () {
    it('adds an injury successfully', function () {
        $user = User::factory()->create();

        $response = WorkoutServer::tool(AddInjuryTool::class, [
            'user_id' => $user->id,
            'injury_type' => 'acute',
            'body_part' => 'knee',
            'started_at' => '2024-01-15',
            'notes' => 'Running injury',
        ]);

        $response->assertOk()
            ->assertSee('knee')
            ->assertSee('acute')
            ->assertSee('Injury added successfully');

        assertDatabaseHas('injuries', [
            'user_id' => $user->id,
            'injury_type' => 'acute',
            'body_part' => 'knee',
            'notes' => 'Running injury',
        ]);
    });

    it('adds a resolved injury with end date', function () {
        $user = User::factory()->create();

        $response = WorkoutServer::tool(AddInjuryTool::class, [
            'user_id' => $user->id,
            'injury_type' => 'chronic',
            'body_part' => 'lower_back',
            'started_at' => '2023-06-01',
            'ended_at' => '2024-01-01',
        ]);

        $response->assertOk()
            ->assertSee('"is_active":false');

        $injury = $user->injuries()->first();
        expect($injury->ended_at->toDateString())->toBe('2024-01-01');
    });

    it('fails with invalid user_id', function () {
        $response = WorkoutServer::tool(AddInjuryTool::class, [
            'user_id' => 99999,
            'injury_type' => 'acute',
            'body_part' => 'knee',
            'started_at' => '2024-01-15',
        ]);

        $response->assertHasErrors()
            ->assertSee('User not found');
    });

    it('fails with invalid injury_type', function () {
        $user = User::factory()->create();

        $response = WorkoutServer::tool(AddInjuryTool::class, [
            'user_id' => $user->id,
            'injury_type' => 'invalid_type',
            'body_part' => 'knee',
            'started_at' => '2024-01-15',
        ]);

        $response->assertHasErrors();
    });

    it('fails with invalid body_part', function () {
        $user = User::factory()->create();

        $response = WorkoutServer::tool(AddInjuryTool::class, [
            'user_id' => $user->id,
            'injury_type' => 'acute',
            'body_part' => 'invalid_part',
            'started_at' => '2024-01-15',
        ]);

        $response->assertHasErrors();
    });

    it('fails when end date is before start date', function () {
        $user = User::factory()->create();

        $response = WorkoutServer::tool(AddInjuryTool::class, [
            'user_id' => $user->id,
            'injury_type' => 'acute',
            'body_part' => 'knee',
            'started_at' => '2024-06-01',
            'ended_at' => '2024-01-01',
        ]);

        $response->assertHasErrors();
    });
});

describe('RemoveInjuryTool', function () {
    it('removes an injury successfully', function () {
        $user = User::factory()->create();
        $injury = Injury::factory()->create([
            'user_id' => $user->id,
            'body_part' => BodyPart::Shoulder,
        ]);

        $response = WorkoutServer::tool(RemoveInjuryTool::class, [
            'user_id' => $user->id,
            'injury_id' => $injury->id,
        ]);

        $response->assertOk()
            ->assertSee('Injury removed successfully')
            ->assertSee('Shoulder');

        assertDatabaseMissing('injuries', ['id' => $injury->id]);
    });

    it('fails with invalid user_id', function () {
        $user = User::factory()->create();
        $injury = Injury::factory()->create(['user_id' => $user->id]);

        $response = WorkoutServer::tool(RemoveInjuryTool::class, [
            'user_id' => 99999,
            'injury_id' => $injury->id,
        ]);

        $response->assertHasErrors()
            ->assertSee('User not found');
    });

    it('fails with invalid injury_id', function () {
        $user = User::factory()->create();

        $response = WorkoutServer::tool(RemoveInjuryTool::class, [
            'user_id' => $user->id,
            'injury_id' => 99999,
        ]);

        $response->assertHasErrors()
            ->assertSee('Injury not found');
    });

    it('fails when injury belongs to another user', function () {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $injury = Injury::factory()->create(['user_id' => $otherUser->id]);

        $response = WorkoutServer::tool(RemoveInjuryTool::class, [
            'user_id' => $user->id,
            'injury_id' => $injury->id,
        ]);

        $response->assertOk()
            ->assertSee('"success":false')
            ->assertSee('does not belong to this user');

        // Injury should still exist
        assertDatabaseHas('injuries', ['id' => $injury->id]);
    });
});
