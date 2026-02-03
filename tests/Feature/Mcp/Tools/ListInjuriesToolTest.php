<?php

use App\Enums\BodyPart;
use App\Enums\InjuryType;
use App\Mcp\Servers\WorkoutServer;
use App\Mcp\Tools\ListInjuriesTool;
use App\Models\Injury;
use App\Models\User;

it('lists active injuries by default', function () {
    $user = User::factory()->create();
    Injury::factory()->for($user)->active()->count(2)->create();
    Injury::factory()->for($user)->resolved()->count(1)->create();

    $response = WorkoutServer::actingAs($user)->tool(ListInjuriesTool::class, []);

    $response->assertOk()
        ->assertSee('"count":2')
        ->assertSee('"filter":"active"');
});

it('lists resolved injuries when filter is resolved', function () {
    $user = User::factory()->create();
    Injury::factory()->for($user)->active()->count(2)->create();
    Injury::factory()->for($user)->resolved()->count(3)->create();

    $response = WorkoutServer::actingAs($user)->tool(ListInjuriesTool::class, [
        'filter' => 'resolved',
    ]);

    $response->assertOk()
        ->assertSee('"count":3')
        ->assertSee('"filter":"resolved"');
});

it('lists all injuries when filter is all', function () {
    $user = User::factory()->create();
    Injury::factory()->for($user)->active()->count(2)->create();
    Injury::factory()->for($user)->resolved()->count(3)->create();

    $response = WorkoutServer::actingAs($user)->tool(ListInjuriesTool::class, [
        'filter' => 'all',
    ]);

    $response->assertOk()
        ->assertSee('"count":5')
        ->assertSee('"filter":"all"');
});

it('returns injury details including id', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->active()->create([
        'injury_type' => InjuryType::Chronic,
        'body_part' => BodyPart::Knee,
        'started_at' => '2026-01-15',
        'notes' => 'Runner\'s knee',
    ]);

    $response = WorkoutServer::actingAs($user)->tool(ListInjuriesTool::class, []);

    $response->assertOk()
        ->assertSee('"id":'.$injury->id)
        ->assertSee('"injury_type":"chronic"')
        ->assertSee('"injury_type_label":"Chronic"')
        ->assertSee('"body_part":"knee"')
        ->assertSee('"body_part_label":"Knee"')
        ->assertSee('"started_at":"2026-01-15"')
        ->assertSee('"is_active":true')
        ->assertSee('Runner\'s knee');
});

it('does not include other users injuries', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    Injury::factory()->for($user)->active()->count(1)->create();
    Injury::factory()->for($otherUser)->active()->count(3)->create();

    $response = WorkoutServer::actingAs($user)->tool(ListInjuriesTool::class, []);

    $response->assertOk()
        ->assertSee('"count":1');
});

it('returns empty list when no injuries', function () {
    $user = User::factory()->create();

    $response = WorkoutServer::actingAs($user)->tool(ListInjuriesTool::class, []);

    $response->assertOk()
        ->assertSee('"injuries":[]')
        ->assertSee('"count":0');
});
