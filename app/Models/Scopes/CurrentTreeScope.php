<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Confines every query to the signed-in person's own tree.
 *
 * Applied as a global scope rather than a condition added at each call site, on
 * purpose: one forgotten `where` in a search box or a JSON endpoint would hand
 * one family another family's records, and there is no way to be sure by
 * reading that every query has remembered. Here it holds by default and has to
 * be taken off deliberately, which is the safer way round.
 *
 * It stays inert when nobody is signed in — console commands, the queue and the
 * account-claim pages all run without a user and legitimately need to reach
 * across trees. Those paths name the tree they mean instead.
 */
class CurrentTreeScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $treeId = Auth::user()?->tree_id;

        if ($treeId === null) {
            return;
        }

        $builder->where($model->qualifyColumn('tree_id'), $treeId);
    }
}
