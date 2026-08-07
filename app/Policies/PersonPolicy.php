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

    /**
     * The global tree scope already keeps one family's records out of another
     * family's queries, so anyone who has got as far as holding a Person here
     * is in the same tree. Checked again rather than assumed: a route model
     * bound by id is exactly where that would otherwise slip.
     */
    public function view(User $user, Person $person): bool
    {
        return $user->sharesTreeWith($person);
    }

    public function create(User $user): bool
    {
        return $user->managesTree();
    }

    /**
     * Whoever runs this tree can edit anyone in it. A claimed adult can edit
     * their own record. A minor's record can only be edited by one of their
     * linked parents — and "minor" is purely age-based, so this naturally cuts
     * off the moment the person turns 18, independent of whether they've
     * claimed their account.
     *
     * Running a tree carries no weight in anyone else's: an Admin, and the
     * Super Admin alike, can only manage the family they belong to.
     */
    public function update(User $user, Person $person): bool
    {
        if ($user->managesTree() && $user->sharesTreeWith($person)) {
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
        return $user->managesTree() && $user->sharesTreeWith($person);
    }

    public function restore(User $user, Person $person): bool
    {
        return $user->managesTree() && $user->sharesTreeWith($person);
    }

    public function forceDelete(User $user, Person $person): bool
    {
        return $user->managesTree() && $user->sharesTreeWith($person);
    }
}
