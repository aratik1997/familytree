<?php

namespace App\Notifications;

use App\Models\TreeMembership;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to somebody another family has asked to place in their tree.
 *
 * This needs an answer, unlike most of what the site sends — nothing shows in
 * that tree until they give one — so it says plainly what agreeing means.
 *
 * Not queued, for the same reason the invitations are not: on shared hosting
 * with no worker, queued mail waits for a worker that never comes.
 */
class TreeMembershipRequested extends Notification
{
    public function __construct(public TreeMembership $membership)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    private function treeName(): string
    {
        return $this->membership->tree?->name ?? __('another family');
    }

    private function askedBy(): string
    {
        return $this->membership->invitedBy?->full_name ?? __('Someone');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__(':name would like to add you to their family tree', ['name' => $this->askedBy()]))
            ->greeting(__('A family would like to include you'))
            ->line(__(':name has asked to place you in :tree.', [
                'name' => $this->askedBy(),
                'tree' => $this->treeName(),
            ]))
            ->line(__('You would keep the one profile you already have — the same photo and details, and the same choices about who sees what. Nothing of yours is copied, and nothing appears in their tree until you agree.'))
            ->action(__('Answer this request'), route('memberships.index'))
            ->line(__('If you do not know them, decline — nothing happens and they are not told why.'))
            ->salutation(__('— The Khandani Legacy'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'tree_membership_requested',
            'membership_id' => $this->membership->id,
            'tree_name' => $this->treeName(),
            'message' => __(':name would like to add you to :tree.', [
                'name' => $this->askedBy(),
                'tree' => $this->treeName(),
            ]),
            'url' => route('memberships.index'),
        ];
    }
}
