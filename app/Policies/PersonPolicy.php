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
        return $user->canManageTree();
    }

    /**
     * Who may edit a profile.
     *
     * The Super Admin may edit anyone: the whole record is theirs to keep.
     *
     * A moderator may edit themselves and the generations below them — their
     * children, their grandchildren, on down. Not their parents, not their
     * uncles, not a cousin's branch. Looking after the tree does not mean
     * rewriting your elders, and the people best placed to correct a line are
     * the ones it descends to.
     *
     * A claimed adult may edit their own record. A minor's record may only be
     * edited by one of their linked parents — and "minor" is purely age-based,
     * so that cuts off the moment the person turns 18, whether or not they have
     * claimed their account.
     */
    public function update(User $user, Person $person): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        if ($person->user_id === $user->id) {
            return true;
        }

        if ($user->canManageTree() && $this->isInOwnLine($user, $person)) {
            return true;
        }

        if ($person->isMinor() && $user->person) {
            return $person->parents->contains('id', $user->person->id);
        }

        return false;
    }

    /**
     * Whether this person is the moderator themselves or descends from them.
     *
     * isAncestorOf walks downwards from the moderator, so it answers exactly
     * the question being asked — is this one of mine, further down?
     */
    private function isInOwnLine(User $user, Person $person): bool
    {
        $self = $user->person;

        if (! $self) {
            return false;
        }

        return $self->id === $person->id || $self->isAncestorOf($person);
    }

    /**
     * Recording that somebody has died is open to any moderator, for anyone in
     * the tree whatever their generation.
     *
     * Deliberately outside the rule above. A death is a fact about the family
     * rather than a change to somebody's own profile, it is most often an elder
     * who has died — exactly the direction a moderator cannot otherwise reach —
     * and leaving the record wrong until the one person entitled to fix it gets
     * round to it serves nobody.
     */
    public function markDeceased(User $user, Person $person): bool
    {
        return $user->canManageTree();
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

    /**
     * Removing somebody from the record altogether stays with the Super Admin.
     * A moderator can correct their own line and can record a death; taking a
     * person out of the family entirely is a different order of thing, and it
     * is the one action here that cannot be undone from the interface.
     */
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
