<?php

namespace App\Models\Concerns;

use App\Models\Scopes\CurrentTreeScope;
use App\Models\Tree;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Ties a record to one family's tree: confines reads to the signed-in person's
 * tree, and stamps new records with it so nothing can be created loose.
 */
trait BelongsToTree
{
    public static function bootBelongsToTree(): void
    {
        static::addGlobalScope(new CurrentTreeScope);

        static::creating(function ($model) {
            $model->tree_id ??= Auth::user()?->tree_id;
        });
    }

    public function tree(): BelongsTo
    {
        return $this->belongsTo(Tree::class);
    }

    /**
     * Whether this kind of record can stand in a tree other than the one it
     * was created in. True for a person, who can be somebody's son in one
     * family and somebody's son-in-law in another. False for a marriage, which
     * belongs to the tree that recorded it and to no other.
     */
    public function lendableAcrossTrees(): bool
    {
        return false;
    }

    /** Reaches across every tree — for console commands and the claim pages. */
    public function scopeAcrossAllTrees($query)
    {
        return $query->withoutGlobalScope(CurrentTreeScope::class);
    }
}
