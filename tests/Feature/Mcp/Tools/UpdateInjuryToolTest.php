<?php

use App\Enums\BodyPart;
use App\Enums\InjuryType;
use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\UpdateInjuryTool;
use App\Models\Injury;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

it('updates injury type', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->create(['injury_type' => InjuryType::Acute]);

    $response = WorkoutServer::actingAs($user)->tool(UpdateInjuryTool::class, [
        'injury_id' => $injury->id,
        'injury_type' => 'chronic',
    ]);

    $response->assertOk()
        ->assertSee('Injury updated successfully')
        ->assertSee('chronic');

    assertDatabaseHas('injuries', [
        'id' => $injury->id,
        'injury_type' => 'chronic',
    ]);
});

it('updates body part', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->create(['body_part' => BodyPart::Knee]);

    $response = WorkoutServer::actingAs($user)->tool(UpdateInjuryTool::class, [
        'injury_id' => $injury->id,
        'body_part' => 'shoulder',
    ]);

    $response->assertOk()
        ->assertSee('Shoulder');

    assertDatabaseHas('injuries', [
        'id' => $injury->id,
        'body_part' => 'shoulder',
    ]);
});

it('updates started_at', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->create(['started_at' => '2025-01-01']);

    $response = WorkoutServer::actingAs($user)->tool(UpdateInjuryTool::class, [
        'injury_id' => $injury->id,
        'started_at' => '2025-06-15',
    ]);

    $response->assertOk()
        ->assertSee('2025-06-15');

    assertDatabaseHas('injuries', [
        'id' => $injury->id,
        'started_at' => '2025-06-15 00:00:00',
    ]);
});

it('updates notes', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->create(['notes' => 'Old notes']);

    $response = WorkoutServer::actingAs($user)->tool(UpdateInjuryTool::class, [
        'injury_id' => $injury->id,
        'notes' => 'Updated notes from physio visit',
    ]);

    $response->assertOk()
        ->assertSee('Updated notes from physio visit');

    assertDatabaseHas('injuries', [
        'id' => $injury->id,
        'notes' => 'Updated notes from physio visit',
    ]);
});

it('marks injury as resolved by setting ended_at', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->active()->for($user)->create(['started_at' => '2025-01-01']);

    $response = WorkoutServer::actingAs($user)->tool(UpdateInjuryTool::class, [
        'injury_id' => $injury->id,
        'ended_at' => '2025-06-01',
    ]);

    $response->assertOk()
        ->assertSee('2025-06-01')
        ->assertSee('"is_active":false');

    assertDatabaseHas('injuries', [
        'id' => $injury->id,
        'ended_at' => '2025-06-01 00:00:00',
    ]);
});

it('reopens injury by setting ended_at to null', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->resolved()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(UpdateInjuryTool::class, [
        'injury_id' => $injury->id,
        'ended_at' => null,
    ]);

    $response->assertOk()
        ->assertSee('"is_active":true');

    assertDatabaseHas('injuries', [
        'id' => $injury->id,
        'ended_at' => null,
    ]);
});

it('clears notes by setting to null', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->create(['notes' => 'Some notes']);

    $response = WorkoutServer::actingAs($user)->tool(UpdateInjuryTool::class, [
        'injury_id' => $injury->id,
        'notes' => null,
    ]);

    $response->assertOk();

    assertDatabaseHas('injuries', [
        'id' => $injury->id,
        'notes' => null,
    ]);
});

it('fails with invalid injury type', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(UpdateInjuryTool::class, [
        'injury_id' => $injury->id,
        'injury_type' => 'nonexistent',
    ]);

    $response->assertHasErrors()
        ->assertSee('Invalid injury type');
});

it('fails with invalid body part', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(UpdateInjuryTool::class, [
        'injury_id' => $injury->id,
        'body_part' => 'nonexistent',
    ]);

    $response->assertHasErrors()
        ->assertSee('Invalid body part');
});

it('fails when both started_at and ended_at are provided and ended_at is before started_at', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->create();

    $response = WorkoutServer::actingAs($user)->tool(UpdateInjuryTool::class, [
        'injury_id' => $injury->id,
        'started_at' => '2025-06-01',
        'ended_at' => '2025-01-01',
    ]);

    $response->assertHasErrors()
        ->assertSee('End date must be on or after the start date');
});

it('fails when ended_at is before existing started_at', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->create(['started_at' => '2025-06-01']);

    $response = WorkoutServer::actingAs($user)->tool(UpdateInjuryTool::class, [
        'injury_id' => $injury->id,
        'ended_at' => '2025-01-01',
    ]);

    $response->assertHasErrors()
        ->assertSee('End date must be on or after the start date');
});

it('fails when started_at is moved after existing ended_at', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->create([
        'started_at' => '2025-01-01',
        'ended_at' => '2025-03-01',
    ]);

    $response = WorkoutServer::actingAs($user)->tool(UpdateInjuryTool::class, [
        'injury_id' => $injury->id,
        'started_at' => '2025-06-01',
    ]);

    $response->assertHasErrors()
        ->assertSee('End date must be on or after the start date');
});

it('fails when updating another user\'s injury', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $otherUser = User::factory()->create();
    $injury = Injury::factory()->for($otherUser)->create();

    $response = WorkoutServer::actingAs($user)->tool(UpdateInjuryTool::class, [
        'injury_id' => $injury->id,
        'notes' => 'Trying to update',
    ]);

    $response->assertHasErrors()
        ->assertSee('Injury not found or does not belong to this user');
});

it('fails with non-existent injury id', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->tool(UpdateInjuryTool::class, [
        'injury_id' => 99999,
        'notes' => 'Trying to update',
    ]);

    $response->assertHasErrors()
        ->assertSee('Injury not found or does not belong to this user');
});
