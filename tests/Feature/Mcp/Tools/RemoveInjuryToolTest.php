<?php

use App\Enums\BodyPart;
use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\RemoveInjuryTool;
use App\Models\Injury;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

it('removes an injury successfully', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->create([
        'user_id' => $user->id,
        'body_part' => BodyPart::Shoulder,
    ]);

    $response = WorkoutServer::actingAs($user)->tool(RemoveInjuryTool::class, [
        'injury_id' => $injury->id,
    ]);

    $response->assertOk()
        ->assertSee('Injury removed successfully')
        ->assertSee('Shoulder');

    assertDatabaseMissing('injuries', ['id' => $injury->id]);
});

it('fails with invalid injury_id', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(RemoveInjuryTool::class, [
        'injury_id' => 99999,
    ]);

    $response->assertHasErrors()
        ->assertSee('Injury not found');
});

it('fails when injury belongs to another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $injury = Injury::factory()->create(['user_id' => $otherUser->id]);

    $response = WorkoutServer::actingAs($user)->tool(RemoveInjuryTool::class, [
        'injury_id' => $injury->id,
    ]);

    $response->assertHasErrors()
        ->assertSee('Injury not found or access denied.');

    // Injury should still exist
    assertDatabaseHas('injuries', ['id' => $injury->id]);
});
