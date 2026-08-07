<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Not ShouldQueue, for the same reason AccountClaimInvite is not: queuing it
 * would leave delivery resting on there being a worker to run the queue.
 */
class AdminInvite extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your family tree is ready — The Khandani Legacy'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-invite',
            with: [
                'claimUrl' => route('password.reset', [
                    'token' => $this->token,
                    'email' => $this->user->email,
                ]),
            ],
        );
    }
}
