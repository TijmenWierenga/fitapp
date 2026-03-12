<?php

use App\Models\Injury;
use App\Models\User;
use App\Notifications\DailyInjuryReportReminder;
use Illuminate\Notifications\Messages\MailMessage;

it('sends a mail notification with injury details', function () {
    $user = User::factory()->create(['name' => 'John']);
    $injury = Injury::factory()->for($user)->create();

    $notification = new DailyInjuryReportReminder($injury);

    expect($notification->via($user))->toBe(['mail']);

    $mail = $notification->toMail($user);

    expect($mail)->toBeInstanceOf(MailMessage::class)
        ->and($mail->subject)->toContain($injury->body_part->label())
        ->and($mail->greeting)->toBe('Hi John,')
        ->and($mail->actionUrl)->toBe(route('injuries.reports', $injury));
});
