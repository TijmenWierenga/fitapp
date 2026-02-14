<?php

use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\GetInjuriesTool;
use App\Models\Injury;
use App\Models\InjuryReport;
use App\Models\User;

it('returns no injuries message when user has none', function () {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->tool(GetInjuriesTool::class);

    $response->assertOk()
        ->assertSee('No injuries recorded');
});

it('returns active and resolved injuries', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    Injury::factory()->for($user)->active()->create(['notes' => 'Sore knee']);
    Injury::factory()->for($user)->resolved()->create();

    $response = WorkoutServer::actingAs($user)->tool(GetInjuriesTool::class);

    $response->assertOk()
        ->assertSee('Active Injuries')
        ->assertSee('Sore knee')
        ->assertSee('Past Injuries');
});

it('includes recent injury reports', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $injury = Injury::factory()->for($user)->active()->create();
    InjuryReport::factory()->for($injury)->for($user)->selfReporting()->create([
        'content' => 'Feeling better today',
    ]);

    $response = WorkoutServer::actingAs($user)->tool(GetInjuriesTool::class);

    $response->assertOk()
        ->assertSee('Feeling better today');
});

it('only returns injuries belonging to the authenticated user', function () {
    $user = User::factory()->withTimezone('UTC')->create();
    $otherUser = User::factory()->withTimezone('UTC')->create();

    Injury::factory()->for($user)->active()->create(['notes' => 'My knee problem']);
    Injury::factory()->for($otherUser)->active()->create(['notes' => 'Someone else shoulder issue']);

    $response = WorkoutServer::actingAs($user)->tool(GetInjuriesTool::class);

    $response->assertOk()
        ->assertSee('My knee problem')
        ->assertDontSee('Someone else shoulder issue');
});
