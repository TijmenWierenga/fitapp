<?php

use App\Mcp\Resources\WorkloadZonesResource;
use App\Mcp\Servers\WorkoutServer;
use App\Models\User;

it('includes acwr zone definitions', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->resource(WorkloadZonesResource::class, []);

    $response->assertOk()
        ->assertSee('Workload Zones')
        ->assertSee('Undertraining')
        ->assertSee('Sweet Spot')
        ->assertSee('Caution')
        ->assertSee('Danger');
});

it('includes acwr ranges', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->resource(WorkloadZonesResource::class, []);

    $response->assertOk()
        ->assertSee('< 0.8')
        ->assertSee('0.8')
        ->assertSee('1.3')
        ->assertSee('1.5');
});

it('includes decision rules', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->resource(WorkloadZonesResource::class, []);

    $response->assertOk()
        ->assertSee('Decision Rules')
        ->assertSee('Avoid')
        ->assertSee('Prioritize')
        ->assertSee('Cross-reference');
});
