<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreParentRequest;
use App\Http\Requests\Admin\StorePersonRequest;
use App\Http\Requests\Admin\StoreSpouseRequest;
use App\Mail\AccountClaimInvite;
use App\Models\ClaimInvite;
use App\Models\Couple;
use App\Models\Person;
use App\Support\ImageStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function index()
    {
        $people = Person::with(['parents', 'user'])
            ->orderBy('full_name')
            ->get();

        return view('admin.dashboard', ['people' => $people]);
    }

    public function editPassword(Person $person)
    {
        abort_unless($person->isClaimed(), 404);

        return view('admin.password', ['person' => $person]);
    }

    public function updatePassword(Request $request, Person $person)
    {
        abort_unless($person->isClaimed(), 404);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $person->user->update(['password' => Hash::make($validated['password'])]);

        return redirect()->route('admin.dashboard')->with('status', 'password-updated');
    }

    /**
     * Manually (re)send a claim invite outside the normal 18th-birthday
     * schedule — e.g. the email bounced, or an admin wants to onboard
     * someone early. Any still-pending invite is invalidated first so a
     * person never has two live tokens outstanding at once.
     */
    public function resendInvite(Person $person)
    {
        abort_if($person->isClaimed(), 404);

        $plainToken = Str::random(64);

        $person->invites()->whereNull('used_at')->update(['used_at' => now()]);

        ClaimInvite::create([
            'person_id' => $person->id,
            'token' => hash('sha256', $plainToken),
            'type' => 'manual_invite',
            'expires_at' => now()->addDays(7),
            'invited_by_person_id' => auth()->user()->person?->id,
        ]);

        $person->update(['claim_status' => 'pending_invite', 'invited_at' => now()]);

        Mail::to($person->email)->queue(new AccountClaimInvite($person, $plainToken));

        // The link is also handed back once, so it can be passed on by hand —
        // over WhatsApp, or in person — without depending on the email
        // arriving. Only its hash is stored, so this is the single moment the
        // real link exists anywhere outside that message.
        return back()
            ->with('status', 'invite-sent')
            ->with('invite_link', route('claim.show', $plainToken))
            ->with('invite_for', $person->full_name)
            ->with('invite_email', $person->email)
            ->with('invite_expires', now()->addDays(7)->format('j M Y'));
    }

    /**
     * Removing the Super Admin's own record here would strand the app with
     * no admin, so that one record is never eligible for this action. If the
     * person has a claimed login, it's deleted too — otherwise they'd keep
     * working access after being removed from the tree.
     */
    public function destroy(Person $person)
    {
        abort_if($person->user?->is_super_admin, 403);

        $user = $person->user;
        $person->update(['user_id' => null]);
        $person->delete();
        $user?->delete();

        return redirect()->route('admin.dashboard')->with('status', 'person-removed');
    }

    /**
     * A new person, wired into the tree immediately: who they're a child of
     * is mandatory (either an existing person or an existing couple), and
     * they can optionally also be made the parent of an existing person in
     * the same step (e.g. adding a grandparent who is also a great-aunt's
     * parent).
     */
    public function createPerson()
    {
        $existingCandidates = Person::orderBy('full_name')->get();

        $existingCouples = Couple::with(['personA', 'personB'])
            ->get()
            ->filter(fn (Couple $couple) => $couple->personA && $couple->personB);

        return view('admin.create-person', [
            'existingCandidates' => $existingCandidates,
            'existingCouples' => $existingCouples,
        ]);
    }

    public function storePerson(StorePersonRequest $request)
    {
        $parents = $this->resolveExistingSelection($request->validated('parent_selection'));

        $targetChild = $request->validated('child_person_id')
            ? Person::findOrFail($request->validated('child_person_id'))
            : null;

        if ($targetChild) {
            foreach ($parents as $parent) {
                if ($parent->id === $targetChild->id || $targetChild->isAncestorOf($parent)) {
                    return back()
                        ->withErrors(['child_person_id' => 'That would make them their own ancestor — not allowed.'])
                        ->withInput();
                }
            }
        }

        $path = ImageStore::put($request->file('photo'), 'profile-photos');

        $person = Person::create([
            'full_name' => $request->validated('full_name'),
            'email' => $request->validated('email'),
            'date_of_birth' => $request->validated('date_of_birth'),
            'gender' => $request->validated('gender'),
            'profile_photo_path' => $path,
            'created_by_person_id' => auth()->user()->person?->id,
        ]);

        foreach ($parents as $parent) {
            $person->parents()->syncWithoutDetaching([
                $parent->id => ['relationship_type' => $request->validated('parent_relationship_type')],
            ]);
        }

        if ($targetChild) {
            $targetChild->parents()->syncWithoutDetaching([
                $person->id => ['relationship_type' => $request->validated('child_relationship_type')],
            ]);
        }

        return redirect()->route('people.show', $person)->with('status', 'person-added');
    }

    /**
     * The mirror of "Add Child": links a person — new or already in the
     * tree — as a parent of an existing person.
     */
    public function createParent(Person $person)
    {
        $excludedIds = $person->parents->pluck('id')->push($person->id);

        $existingCandidates = Person::whereNotIn('id', $excludedIds)->orderBy('full_name')->get();

        $existingCouples = Couple::with(['personA', 'personB'])
            ->where('person_a_id', '!=', $person->id)
            ->where('person_b_id', '!=', $person->id)
            ->get()
            ->filter(fn (Couple $couple) => $couple->personA && $couple->personB);

        return view('admin.create-parent', [
            'child' => $person,
            'existingCandidates' => $existingCandidates,
            'existingCouples' => $existingCouples,
        ]);
    }

    public function storeParent(StoreParentRequest $request, Person $person)
    {
        if ($request->validated('mode') === 'existing') {
            $parents = $this->resolveExistingSelection($request->validated('existing_person_id'));

            foreach ($parents as $parent) {
                if ($person->isAncestorOf($parent)) {
                    return back()
                        ->withErrors(['existing_person_id' => 'That would make them their own ancestor — not allowed.'])
                        ->withInput();
                }
            }
        } else {
            $path = ImageStore::put($request->file('photo'), 'profile-photos');

            $parents = [Person::create([
                'full_name' => $request->validated('full_name'),
                'email' => $request->validated('email'),
                'date_of_birth' => $request->validated('date_of_birth'),
                'gender' => $request->validated('gender'),
                'profile_photo_path' => $path,
                'created_by_person_id' => auth()->user()->person?->id,
            ])];
        }

        foreach ($parents as $parent) {
            $person->parents()->syncWithoutDetaching([
                $parent->id => ['relationship_type' => $request->validated('relationship_type')],
            ]);
        }

        return redirect()->route('people.show', $person)->with('status', 'parent-added');
    }

    /**
     * The existing-person select is either a plain person id, or
     * "couple:idA-idB" when both members of a couple were picked at once.
     *
     * @return array<Person>
     */
    private function resolveExistingSelection(string $value): array
    {
        if (str_starts_with($value, 'couple:')) {
            $ids = explode('-', substr($value, 7));

            return Person::whereIn('id', $ids)->get()->all();
        }

        return [Person::findOrFail($value)];
    }

    /**
     * Adds a spouse (wife, husband, or partner) — either a brand-new person
     * or an existing one already in the tree — as a `couples` record.
     */
    public function createSpouse(Person $person)
    {
        $existingSpouseIds = $person->spouses()->pluck('id')->push($person->id);

        $candidates = Person::whereNotIn('id', $existingSpouseIds)
            ->when($person->gender === 'male', fn ($query) => $query->where('gender', 'female'))
            ->when($person->gender === 'female', fn ($query) => $query->where('gender', 'male'))
            ->orderBy('full_name')
            ->get();

        $oppositeGender = match ($person->gender) {
            'male' => 'female',
            'female' => 'male',
            default => null,
        };

        return view('admin.create-spouse', [
            'person' => $person,
            'candidates' => $candidates,
            'oppositeGender' => $oppositeGender,
        ]);
    }

    public function storeSpouse(StoreSpouseRequest $request, Person $person)
    {
        if ($request->validated('mode') === 'existing') {
            $spouse = Person::findOrFail($request->validated('existing_person_id'));
        } else {
            $path = ImageStore::put($request->file('photo'), 'profile-photos');

            $spouse = Person::create([
                'full_name' => $request->validated('full_name'),
                'email' => $request->validated('email'),
                'date_of_birth' => $request->validated('date_of_birth'),
                'gender' => $request->validated('gender'),
                'profile_photo_path' => $path,
                'created_by_person_id' => auth()->user()->person?->id,
            ]);
        }

        Couple::create([
            'person_a_id' => $person->id,
            'person_b_id' => $spouse->id,
            'status' => $request->validated('status'),
            'started_on' => $request->validated('started_on'),
            'ended_on' => $request->validated('ended_on'),
        ]);

        return redirect()->route('people.show', $person)->with('status', 'spouse-added');
    }

    /**
     * Drag-and-drop reparenting on the tree page: link an existing person as
     * a parent of another existing person. Rejects anything that would make
     * someone their own ancestor.
     */
    public function attachParent(Request $request)
    {
        $validated = $request->validate([
            'child_id' => ['required', 'integer', 'different:parent_id', 'exists:people,id'],
            'parent_id' => ['required', 'integer', 'exists:people,id'],
            'relationship_type' => ['nullable', 'in:biological,step,adoptive,guardian'],
        ]);

        $child = Person::findOrFail($validated['child_id']);
        $parent = Person::findOrFail($validated['parent_id']);

        if ($child->isAncestorOf($parent)) {
            return response()->json(['message' => 'That would make them their own ancestor — not allowed.'], 422);
        }

        // Someone cannot be both married to a person and a child of them. The
        // two rules the chart is drawn from — a couple shares a row, a child
        // sits below its parents — cannot both hold for such a pair, and the
        // layout has no sensible row left to put either of them in.
        if ($child->isMarriedTo($parent)) {
            return response()->json([
                'message' => 'They are recorded as married to each other, so one cannot also be the other\'s child.',
            ], 422);
        }

        $child->parents()->syncWithoutDetaching([
            $parent->id => ['relationship_type' => $validated['relationship_type'] ?? 'biological'],
        ]);

        return response()->json(['message' => 'Linked.']);
    }

    /**
     * Breaks a parent-and-child link. Answers JSON to the tree, which talks to
     * this over fetch, and redirects back for the plain form on the edit page.
     */
    public function detachParent(Request $request, Person $child, Person $parent)
    {
        $child->parents()->detach($parent->id);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unlinked.']);
        }

        return back()->with('status', 'relationship-removed');
    }
}
