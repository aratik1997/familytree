<?php

namespace App\Console\Commands;

use App\Mail\AccountClaimInvite;
use App\Models\ClaimInvite;
use App\Models\Person;
use App\Notifications\ChildTurnedEighteen;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class ProcessAdultTransitions extends Command
{
    protected $signature = 'app:process-adult-transitions';

    protected $description = "Email an account-claim invite to anyone who turned 18 and hasn't claimed their account yet";

    public function handle(): int
    {
        $count = 0;

        Person::whereNull('user_id')
            ->where('claim_status', '!=', 'pending_invite')
            ->whereDate('date_of_birth', '<=', now()->subYears(18))
            ->chunkById(100, function ($people) use (&$count) {
                foreach ($people as $person) {
                    $plainToken = Str::random(64);

                    DB::transaction(function () use ($person, $plainToken) {
                        ClaimInvite::create([
                            'person_id' => $person->id,
                            'token' => hash('sha256', $plainToken),
                            'type' => 'adult_claim',
                            'expires_at' => now()->addDays(7),
                        ]);

                        $person->update(['claim_status' => 'pending_invite', 'invited_at' => now()]);
                    });

                    Mail::to($person->email)->queue(new AccountClaimInvite($person, $plainToken));
                    $this->notifyParents($person);
                    $count++;
                }
            });

        $this->info("Sent {$count} claim invite(s).");

        return self::SUCCESS;
    }

    /**
     * Let the parents know, since this same birthday is what ends their
     * ability to edit the profile — without a word they would just find it
     * had gone read-only.
     *
     * A parent with a login gets it in-app and by email; one who has never
     * claimed an account can only be reached by email.
     */
    private function notifyParents(Person $person): void
    {
        foreach ($person->parents as $parent) {
            $notification = new ChildTurnedEighteen($person);

            if ($parent->user) {
                $parent->user->notify($notification);
                continue;
            }

            if ($parent->email) {
                Notification::route('mail', $parent->email)->notify($notification);
            }
        }
    }
}
