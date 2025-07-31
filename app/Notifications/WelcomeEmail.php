<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class WelcomeEmail extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        $timestamp = time();
        $hash = sha1($notifiable->email . $timestamp);
        $verificationUrl = config('app.url') . '/api/verify-email/' . $hash . '?t=' . $timestamp;

        return (new MailMessage)
            ->subject('Welcome to sulus.ai! 🎉')
            ->view('emails.welcome', [
                'user' => $notifiable,
                'verificationUrl' => $verificationUrl,
                'headerTitle' => 'Welcome to sulus.ai!',
                'headerSubtitle' => 'Your Voice AI Platform',
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
