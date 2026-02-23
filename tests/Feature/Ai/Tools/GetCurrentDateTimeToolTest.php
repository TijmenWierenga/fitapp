<?php

use App\Ai\Tools\GetCurrentDateTimeTool;
use App\Models\User;
use Carbon\CarbonImmutable;
use Laravel\Ai\Tools\Request;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns the current date and time in the user\'s timezone', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 2, 23, 10, 30, 0, 'UTC'));

    $user = User::factory()->withTimezone('Europe/Amsterdam')->create();
    $this->actingAs($user);

    $tool = new GetCurrentDateTimeTool;
    $result = json_decode($tool->handle(new Request([])), true);

    expect($result)
        ->timezone->toBe('Europe/Amsterdam')
        ->date->toBe('2026-02-23')
        ->time->toBe('11:30')
        ->day_of_week->toBe('Monday')
        ->and($result['date_time'])->toContain('2026-02-23T11:30:00');
});

it('defaults to UTC when the user has no timezone', function () {
    CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 2, 23, 10, 30, 0, 'UTC'));

    $user = User::factory()->create(['timezone' => null]);
    $this->actingAs($user);

    $tool = new GetCurrentDateTimeTool;
    $result = json_decode($tool->handle(new Request([])), true);

    expect($result)
        ->timezone->toBe('UTC')
        ->date->toBe('2026-02-23')
        ->time->toBe('10:30');
});
