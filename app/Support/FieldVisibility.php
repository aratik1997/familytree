<?php

namespace App\Support;

use App\Models\Person;
use App\Models\User;

/**
 * Field-level privacy is checked here rather than through a Policy, since
 * Policies key on a model instance, not a model+field pair. This is used
 * both by Blade views (hide the field) and by JSON resources (never send
 * the value at all) — the tree/profile JSON endpoints must not leak private
 * data into page source even when the UI wouldn't render it.
 */
class FieldVisibility
{
    public static function canSee(User $viewer, Person $subject, string $visibility): bool
    {
        // Whoever runs the tree sees everything in it — and nothing at all in
        // anyone else's, which the tree check is what enforces. Without it,
        // running one family's tree would open every other family's private
        // fields, since this is the gate the JSON endpoints go through.
        if ($viewer->managesTree() && $viewer->sharesTreeWith($subject)) {
            return true;
        }

        if ($visibility === 'everyone') {
            return true;
        }

        $viewerPerson = $viewer->person;

        if (! $viewerPerson) {
            return false;
        }

        if ($viewerPerson->id === $subject->id) {
            return true;
        }

        $isManagingParent = $subject->isMinor() && $subject->parents->contains('id', $viewerPerson->id);

        return match ($visibility) {
            'private' => $isManagingParent,
            'family' => $isManagingParent || $viewerPerson->isFamilyOf($subject),
            default => false,
        };
    }

    /**
     * The configured visibility for one of the fixed baseline fields
     * (full_name, email, mobile, address, social_links, profile_photo_path, bio).
     */
    public static function visibilityFor(Person $person, string $fieldKey): string
    {
        return $person->profileFieldPrivacy
            ->firstWhere('field_key', $fieldKey)
            ?->visibility
            ?? config("privacy.defaults.{$fieldKey}", 'family');
    }
}
