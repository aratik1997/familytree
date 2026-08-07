<?php

namespace App\Mail;

use App\Models\Person;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Deliberately not ShouldQueue. Marking it so would make Mail::send() defer to
 * the queue no matter how it is called, and on shared hosting with no worker
 * that leaves delivery resting on QUEUE_CONNECTION staying "sync" — Laravel's
 * own default is "database", where the invitation would sit unsent forever and
 * nothing would say so.
 */
class AccountClaimInvite extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Person $person,
        public string $plainToken,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You\'re invited to claim your account — The Khandani Legacy',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account-claim-invite',
            with: [
                'claimUrl' => route('claim.show', $this->plainToken),
            ],
        );
    }
}
