<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Confines every query to the tree currently being looked at.
 *
 * Applied as a global scope rather than a condition added at each call site, on
 * purpose: one forgotten `where` in a search box or a JSON endpoint would hand
 * one family another family's records, and there is no way to be sure by
 * reading that every query has remembered. Here it holds by default and has to
 * be taken off deliberately, which is the safer way round.
 *
 * For people it is not a plain match on tree_id. Somebody can stand in more
 * than one family — a man is his father's son in one tree and his
 * father-in-law's son-in-law in another — and it is the same record in both
 * rather than a copy. So a person belongs to the tree they were entered in, or
 * to any tree that has accepted them. A membership still waiting on their
 * answer counts for nothing: that is what keeps a pending request invisible.
 *
 * It stays inert when nobody is signed in — console commands, the queue and the
 * account-claim pages all run without a user and legitimately need to reach
 * across trees. Those paths name the tree they mean instead.
 */
class CurrentTreeScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $treeId = Auth::user()?->currentTreeId();

        if ($treeId === null) {
            return;
        }

        $column = $model->qualifyColumn('tree_id');

        if (! $model->lendableAcrossTrees()) {
            $builder->where($column, $treeId);

            return;
        }

        $builder->where(function (Builder $query) use ($column, $treeId, $model) {
            $query->where($column, $treeId)
                ->orWhereExists(function ($sub) use ($treeId, $model) {
                    $sub->selectRaw('1')
                        ->from('tree_memberships')
                        ->whereColumn('tree_memberships.person_id', $model->qualifyColumn('id'))
                        ->where('tree_memberships.tree_id', $treeId)
                        ->where('tree_memberships.status', 'accepted');
                });
        });
    }
}
