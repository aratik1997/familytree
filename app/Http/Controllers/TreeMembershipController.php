<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\TreeMembership;
use App\Notifications\TreeMembershipRequested;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Asking somebody to stand in your family's tree, and their answer.
 *
 * The same man is his father's son in one tree and his father-in-law's
 * son-in-law in another. Rather than each family keeping its own half-copy of
 * him, his record is lent from the tree it was entered in — one profile, one
 * photo, one set of privacy choices, showing the same in both places.
 *
 * Nothing appears until he says yes. Being written into somebody's family is
 * not a thing that should happen to you without your say-so.
 */
class TreeMembershipController extends Controller
{
    /** Requests waiting on this person's answer, and the ones they have made. */
    public function index(Request $request)
    {
        $person = $request->user()->person;

        $waitingOnMe = $person
            ? TreeMembership::with(['tree', 'invitedBy'])
                ->where('person_id', $person->id)
                ->where('status', 'pending')
                ->latest()
                ->get()
            : collect();

        $fromMyTree = TreeMembership::with(['person', 'tree'])
            ->where('tree_id', $request->user()->currentTreeId())
            ->latest()
            ->get();

        return view('tree.memberships', [
            'waitingOnMe' => $waitingOnMe,
            'fromMyTree' => $fromMyTree,
        ]);
    }

    /**
     * Invites somebody into the current tree by the code on their profile.
     *
     * Quoted rather than searched for: letting anyone look up a stranger by
     * name would turn the whole site into a directory of families, which is the
     * one thing it is not. A code has to be given to you.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'public_id' => ['required', 'string', 'max:12'],
        ]);

        $treeId = $request->user()->currentTreeId();

        $person = Person::withoutGlobalScopes()
            ->where('public_id', strtoupper(trim($validated['public_id'])))
            ->first();

        if (! $person) {
            throw ValidationException::withMessages([
                'public_id' => __('No one has that ID. Check it with them — it is on their own profile page.'),
            ]);
        }

        if ($person->tree_id === $treeId) {
            throw ValidationException::withMessages([
                'public_id' => __('They are already in this tree.'),
            ]);
        }

        $existing = TreeMembership::where('person_id', $person->id)->where('tree_id', $treeId)->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'public_id' => $existing->isPending()
                    ? __('They have already been asked, and have not answered yet.')
                    : __('They have already answered this request.'),
            ]);
        }

        $membership = TreeMembership::create([
            'person_id' => $person->id,
            'tree_id' => $treeId,
            'status' => 'pending',
            'invited_by_person_id' => $request->user()->person?->id,
        ]);

        // Only somebody with a login can be asked: the answer has to come from
        // them, and there is nowhere for an unclaimed person to give it.
        $person->user?->notify(new TreeMembershipRequested($membership));

        return back()->with('status', 'membership-requested');
    }

    public function accept(Request $request, TreeMembership $membership)
    {
        $this->authoriseAnswer($request, $membership);

        $membership->update(['status' => 'accepted', 'responded_at' => now()]);

        return back()->with('status', 'membership-accepted');
    }

    public function decline(Request $request, TreeMembership $membership)
    {
        $this->authoriseAnswer($request, $membership);

        $membership->update(['status' => 'declined', 'responded_at' => now()]);

        return back()->with('status', 'membership-declined');
    }

    /** Only the person being asked may answer, and only once. */
    private function authoriseAnswer(Request $request, TreeMembership $membership): void
    {
        abort_unless($request->user()->person?->id === $membership->person_id, 403);
        abort_unless($membership->isPending(), 403);
    }

    /**
     * Switches which tree is being looked at. Only ever to one they can
     * already see — their own, or one that has accepted them.
     */
    public function switchTree(Request $request, int $tree)
    {
        abort_unless($request->user()->canSeeTree($tree), 403);

        $request->session()->put('current_tree_id', $tree);

        return redirect()->route('tree.index');
    }
}
