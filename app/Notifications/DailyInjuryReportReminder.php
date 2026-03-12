<?php

namespace App\Notifications;

use App\Models\Injury;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyInjuryReportReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Injury $injury) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Daily Injury Report Reminder: {$this->injury->body_part->label()}")
            ->greeting("Hi {$notifiable->name},")
            ->line("It's time to log your daily injury report for your {$this->injury->body_part->label()} ({$this->injury->injury_type->label()}).")
            ->line('Tracking your pain level daily helps monitor your recovery progress.')
            ->action('Submit Report', route('injuries.reports', $this->injury))
            ->line('Stay consistent with your reports for the best recovery insights.');
    }
}
