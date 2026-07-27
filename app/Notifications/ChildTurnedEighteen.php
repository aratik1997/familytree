<?php

namespace App\Notifications;

use App\Models\Person;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a parent on the day their child turns 18.
 *
 * That birthday is also the moment the parent's edit access falls away and
 * the child is invited to claim their own account, so this is a courtesy
 * heads-up rather than an action item — it explains why the profile they used
 * to manage has just gone read-only for them.
 */
class ChildTurnedEighteen extends Notification
{
    public function __construct(public Person $child)
    {
    }

    /**
     * In-app for anyone with a login, and always an email — a parent who has
     * never claimed an account has no inbox in here to read.
     *
     * Deliberately not queued: this fires once a day from the scheduler for a
     * handful of people at most, and sending inline means the in-app bell
     * appears immediately rather than waiting on a queue worker.
     */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable
            ? ['mail']
            : ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__(':name has turned 18', ['name' => $this->child->full_name]))
            ->greeting(__('A milestone in the family'))
            ->line(__(':name turned 18 today.', ['name' => $this->child->full_name]))
            ->line(__('From now on they look after their own profile, so it is no longer yours to edit. We have invited them to claim their account.'))
            ->action(__('View their profile'), route('people.show', $this->child))
            ->salutation(__('— The Khandani Legacy'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'child_turned_18',
            'person_id' => $this->child->id,
            'person_name' => $this->child->full_name,
            'message' => __(':name has turned 18 and now looks after their own profile.', [
                'name' => $this->child->full_name,
            ]),
            'url' => route('people.show', $this->child),
        ];
    }
}
