<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SendOtpNotification extends Notification
{
    public function __construct(private string $otp) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('🔐 Your Taskora Verification Code')
            ->greeting('Hello, ' . ($notifiable->name ?? 'User') . '!')
            ->line('You are receiving this email because we received a password reset request for your Taskora account.')
            ->line('Use the following code to verify your identity:')
            ->line('**' . $this->otp . '**')
            ->line('This code will expire in **5 minutes** for security reasons.')
            ->line('If you did not request this, no further action is required.')
            ->salutation('Best regards, <br> The Taskora Team');
    }
}
