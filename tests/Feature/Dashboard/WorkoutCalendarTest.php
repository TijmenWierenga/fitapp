<?php

use App\Livewire\Dashboard\WorkoutCalendar;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;

test('calendar weeks start on Sunday', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(WorkoutCalendar::class)
        ->set('year', 2026)
        ->set('month', 2);

    $weeks = $component->get('calendarWeeks');

    // Get the first day of the first week
    $firstDay = $weeks[0][0]['date'];

    // Verify it's a Sunday (dayOfWeek 0)
    expect($firstDay->dayOfWeek)->toBe(Carbon::SUNDAY)
        ->and($firstDay->format('l'))->toBe('Sunday');
});

test('calendar days align correctly with day headers', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(WorkoutCalendar::class)
        ->set('year', 2026)
        ->set('month', 2);

    $weeks = $component->get('calendarWeeks');

    // Headers are: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']
    // So each row should have Sunday at index 0, Monday at 1, etc.

    foreach ($weeks as $week) {
        // Each week should have 7 days
        expect($week)->toHaveCount(7);

        // Verify each day is in the correct position
        foreach ($week as $index => $day) {
            expect($day['date']->dayOfWeek)->toBe($index);
        }
    }
});

test('specific date appears in correct column', function () {
    $user = User::factory()->create();

    // February 4, 2026 is a Wednesday
    $component = Livewire::actingAs($user)
        ->test(WorkoutCalendar::class)
        ->set('year', 2026)
        ->set('month', 2);

    $weeks = $component->get('calendarWeeks');

    // Find February 4th in the calendar
    $foundDate = null;
    $foundDayIndex = null;

    foreach ($weeks as $week) {
        foreach ($week as $index => $day) {
            if ($day['date']->format('Y-m-d') === '2026-02-04') {
                $foundDate = $day['date'];
                $foundDayIndex = $index;
                break 2;
            }
        }
    }

    expect($foundDate)->not->toBeNull()
        ->and($foundDate->format('l'))->toBe('Wednesday')
        ->and($foundDayIndex)->toBe(3); // Wednesday is at index 3 (Sun=0, Mon=1, Tue=2, Wed=3)
});

test('calendar weeks end on Saturday', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(WorkoutCalendar::class)
        ->set('year', 2026)
        ->set('month', 2);

    $weeks = $component->get('calendarWeeks');

    foreach ($weeks as $week) {
        // Last day of each week should be Saturday
        $lastDay = end($week)['date'];
        expect($lastDay->dayOfWeek)->toBe(Carbon::SATURDAY)
            ->and($lastDay->format('l'))->toBe('Saturday');
    }
});
