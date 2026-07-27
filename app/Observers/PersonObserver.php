<?php

namespace App\Observers;

use App\Models\Person;

class PersonObserver
{
    /**
     * Seed a privacy row for every baseline field so the profile edit form
     * always has a visibility setting to show/change, right from creation.
     */
    public function created(Person $person): void
    {
        $rows = collect(config('privacy.defaults'))
            ->map(fn (string $visibility, string $fieldKey) => [
                'person_id' => $person->id,
                'field_key' => $fieldKey,
                'visibility' => $visibility,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        $person->profileFieldPrivacy()->insert($rows);
    }
}
