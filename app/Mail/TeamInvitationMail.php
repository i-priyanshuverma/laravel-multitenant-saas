<?php

namespace App\Mail;

use App\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public TeamInvitation $invitation
    ) {}

    public function envelope(): Envelope
    {
        $tenantName = $this->invitation->tenant->name ?? 'Workspace';

        return new Envelope(
            subject: "You're invited to join {$tenantName} on our SaaS platform",
        );
    }

    public function content(): Content
    {
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
        $acceptUrl = 'http://'.$appHost.'/invitations/'.$this->invitation->token.'/accept';

        return new Content(
            view: 'emails.team-invitation',
            with: [
                'invitation' => $this->invitation,
                'tenant' => $this->invitation->tenant,
                'acceptUrl' => $acceptUrl,
            ],
        );
    }
}
