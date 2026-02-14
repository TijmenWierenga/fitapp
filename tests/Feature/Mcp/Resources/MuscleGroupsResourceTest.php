<?php

use App\Enums\BodyPart;
use App\Mcp\Resources\MuscleGroupsResource;
use App\Mcp\Servers\WorkoutServer;
use App\Models\MuscleGroup;
use App\Models\User;

it('returns muscle groups grouped by region', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    MuscleGroup::factory()->create(['name' => 'chest', 'label' => 'Chest', 'body_part' => BodyPart::Chest]);
    MuscleGroup::factory()->create(['name' => 'quadriceps', 'label' => 'Quadriceps', 'body_part' => BodyPart::Quadriceps]);
    MuscleGroup::factory()->create(['name' => 'abdominals', 'label' => 'Abdominals', 'body_part' => BodyPart::Core]);

    $response = WorkoutServer::actingAs($user)->resource(MuscleGroupsResource::class, []);

    $response->assertOk()
        ->assertSee('Available Muscle Groups')
        ->assertSee('Upper Body')
        ->assertSee('Lower Body')
        ->assertSee('Core & Spine');
});

it('shows empty state when no muscle groups exist', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();

    $response = WorkoutServer::actingAs($user)->resource(MuscleGroupsResource::class, []);

    $response->assertOk()
        ->assertSee('No muscle groups available');
});

it('includes name, label, and body part for each muscle group', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    MuscleGroup::factory()->create(['name' => 'lats', 'label' => 'Lats', 'body_part' => BodyPart::UpperBack]);

    $response = WorkoutServer::actingAs($user)->resource(MuscleGroupsResource::class, []);

    $response->assertOk()
        ->assertSee('lats')
        ->assertSee('Lats')
        ->assertSee('Upper Back');
});

it('mentions search-exercises usage in output', function (): void {
    $user = User::factory()->withTimezone('UTC')->create();
    MuscleGroup::factory()->create(['name' => 'chest', 'label' => 'Chest', 'body_part' => BodyPart::Chest]);

    $response = WorkoutServer::actingAs($user)->resource(MuscleGroupsResource::class, []);

    $response->assertOk()
        ->assertSee('search-exercises');
});
