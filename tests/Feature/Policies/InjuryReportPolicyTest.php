<?php

use App\Models\Injury;
use App\Models\InjuryReport;
use App\Models\User;
use App\Policies\InjuryReportPolicy;

beforeEach(function () {
    $this->policy = new InjuryReportPolicy;
});

it('allows injury owner to view any reports', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->create();

    expect($this->policy->viewAny($user, $injury))->toBeTrue();
});

it('denies non-owner from viewing any reports', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $injury = Injury::factory()->for($other)->create();

    expect($this->policy->viewAny($user, $injury))->toBeFalse();
});

it('allows injury owner to view a report', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->create();
    $report = InjuryReport::factory()->for($injury)->for($user)->create();

    expect($this->policy->view($user, $report))->toBeTrue();
});

it('denies non-owner from viewing a report', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $injury = Injury::factory()->for($other)->create();
    $report = InjuryReport::factory()->for($injury)->for($other)->create();

    expect($this->policy->view($user, $report))->toBeFalse();
});

it('allows injury owner to create a report', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->create();

    expect($this->policy->create($user, $injury))->toBeTrue();
});

it('denies non-owner from creating a report', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $injury = Injury::factory()->for($other)->create();

    expect($this->policy->create($user, $injury))->toBeFalse();
});

it('allows report author to update their report', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->create();
    $report = InjuryReport::factory()->for($injury)->for($user)->create();

    expect($this->policy->update($user, $report))->toBeTrue();
});

it('denies non-author from updating a report', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $injury = Injury::factory()->for($owner)->create();
    $report = InjuryReport::factory()->for($injury)->for($owner)->create();

    expect($this->policy->update($other, $report))->toBeFalse();
});

it('allows report author to delete their report', function () {
    $user = User::factory()->create();
    $injury = Injury::factory()->for($user)->create();
    $report = InjuryReport::factory()->for($injury)->for($user)->create();

    expect($this->policy->delete($user, $report))->toBeTrue();
});

it('allows injury owner to delete any report on their injury', function () {
    $owner = User::factory()->create();
    $author = User::factory()->create();
    $injury = Injury::factory()->for($owner)->create();
    $report = InjuryReport::factory()->for($injury)->for($author)->create();

    expect($this->policy->delete($owner, $report))->toBeTrue();
});

it('denies non-owner non-author from deleting a report', function () {
    $owner = User::factory()->create();
    $author = User::factory()->create();
    $stranger = User::factory()->create();
    $injury = Injury::factory()->for($owner)->create();
    $report = InjuryReport::factory()->for($injury)->for($author)->create();

    expect($this->policy->delete($stranger, $report))->toBeFalse();
});
