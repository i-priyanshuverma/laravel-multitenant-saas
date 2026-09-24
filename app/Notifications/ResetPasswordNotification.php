<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token,
        public ?Tenant $tenant = null,
    ) {}

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
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
        $email = property_exists($notifiable, 'email') ? $notifiable->email : '';

        $baseUrl = $this->tenant
            ? 'http://'.$this->tenant->slug.'.'.$appHost
            : (string) config('app.url');

        $resetUrl = rtrim($baseUrl, '/').'/reset-password/'.$this->token.'?email='.urlencode((string) $email);

        return (new MailMessage)
            ->subject('Reset Your Workspace Password')
            ->greeting('Hello,')
            ->line('You are receiving this email because we received a password reset request for your workspace account.')
            ->action('Reset Password', $resetUrl)
            ->line('This password reset link will expire in 60 minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}
