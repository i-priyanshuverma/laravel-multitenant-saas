<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PlanLimitThresholdNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tenant $tenant,
        public string $resource,
        public int $currentUsage,
        public int $maxLimit,
        public int $threshold,
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
        $billingUrl = 'http://'.$this->tenant->slug.'.'.$appHost.'/billing';
        $notifiableName = property_exists($notifiable, 'name') ? $notifiable->name : 'Team Admin';

        $isCritical = $this->threshold >= 100;
        $subject = $isCritical
            ? "[Action Required] {$this->tenant->name} has reached its {$this->resource} limit"
            : "[Notice] {$this->tenant->name} has reached {$this->threshold}% of its {$this->resource} limit";

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiableName},");

        if ($isCritical) {
            $mail->error()
                ->line("Your organization **{$this->tenant->name}** has reached 100% of its {$this->resource} limit.")
                ->line("Current usage: **{$this->currentUsage} / {$this->maxLimit}** {$this->resource}.")
                ->line('Further additions are blocked until your plan is upgraded.');
        } else {
            $mail->line("Your organization **{$this->tenant->name}** is approaching its plan limit for {$this->resource}.")
                ->line("Current usage: **{$this->currentUsage} / {$this->maxLimit}** {$this->resource} ({$this->threshold}%).")
                ->line('We recommend upgrading your plan now to prevent any interruption in your team workflows.');
        }

        return $mail->action('Upgrade Plan', $billingUrl)
            ->line('Thank you for choosing our platform!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
        $billingUrl = 'http://'.$this->tenant->slug.'.'.$appHost.'/billing';

        return [
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $this->tenant->name,
            'resource' => $this->resource,
            'current_usage' => $this->currentUsage,
            'max_limit' => $this->maxLimit,
            'threshold' => $this->threshold,
            'billing_url' => $billingUrl,
        ];
    }
}
