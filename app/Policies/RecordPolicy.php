<?php

namespace App\Policies;

use App\Models\Person;
use App\Models\Record;
use App\Models\User;
use App\Support\FieldVisibility;

class RecordPolicy
{
    public function view(User $user, Record $record): bool
    {
        return FieldVisibility::canSee($user, $record->person, $record->visibility);
    }

    public function create(User $user, Person $person): bool
    {
        return $user->can('manageRecords', $person);
    }

    public function update(User $user, Record $record): bool
    {
        return $user->can('manageRecords', $record->person);
    }

    public function delete(User $user, Record $record): bool
    {
        return $user->can('manageRecords', $record->person);
    }
}
