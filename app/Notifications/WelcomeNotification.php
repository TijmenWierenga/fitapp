<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to '.config('app.name'))
            ->greeting("Hi {$notifiable->name}!")
            ->line('Welcome! Your account has been created successfully.')
            ->line('Please verify your email address to get full access to all features.')
            ->line('Once verified, you can start logging workouts, tracking progress, and chatting with your AI coach.')
            ->action('Verify Email Address', url('/email/verify'))
            ->line('We\'re excited to have you on board!');
    }
}
