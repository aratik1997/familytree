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

    /** Reaches across every tree — for console commands and the claim pages. */
    public function scopeAcrossAllTrees($query)
    {
        return $query->withoutGlobalScope(CurrentTreeScope::class);
    }
}
