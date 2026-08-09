<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A person lent to a family tree that is not the one they were entered in.
 *
 * Deliberately not scoped to the current tree: both sides of it need to be
 * readable from either tree — the host to see who they have asked for, the
 * guest to see who is asking.
 */
class TreeMembership extends Model
{
    protected $fillable = [
        'person_id',
        'tree_id',
        'status',
        'invited_by_person_id',
        'responded_at',
    ];

    protected function casts(): array
    {
        return ['responded_at' => 'datetime'];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class)->withoutGlobalScopes();
    }

    public function tree(): BelongsTo
    {
        return $this->belongsTo(Tree::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'invited_by_person_id')->withoutGlobalScopes();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
