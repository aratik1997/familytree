<?php

namespace App\Policies;

use App\Models\Person;
use App\Models\User;

class PersonPolicy
{
    /**
     * Any logged-in family member can browse the tree and open profile pages —
     * per-field visibility (see FieldVisibility) controls what they actually see.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Person $person): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->is_super_admin;
    }

    /**
     * Super Admin can edit anyone. A claimed adult can edit their own record.
     * A minor's record can only be edited by one of their linked parents —
     * and "minor" is purely age-based, so this naturally cuts off the moment
     * the person turns 18, independent of whether they've claimed their account.
     */
    public function update(User $user, Person $person): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        if ($person->user_id === $user->id) {
            return true;
        }

        if ($person->isMinor() && $user->person) {
            return $person->parents->contains('id', $user->person->id);
        }

        return false;
    }

    /**
     * Adding a child under a person requires the same standing as editing
     * that person — you can't extend the tree under someone you can't manage.
     */
    public function addChild(User $user, Person $parent): bool
    {
        return $this->update($user, $parent);
    }

    /**
     * Academic/photo/moment/career/other records follow the same ownership
     * rule as the profile itself.
     */
    public function manageRecords(User $user, Person $person): bool
    {
        return $this->update($user, $person);
    }

    public function delete(User $user, Person $person): bool
    {
        return $user->is_super_admin;
    }

    public function restore(User $user, Person $person): bool
    {
        return $user->is_super_admin;
    }

    public function forceDelete(User $user, Person $person): bool
    {
        return $user->is_super_admin;
    }
}
