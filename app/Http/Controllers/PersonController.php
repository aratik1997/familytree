<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChildPersonRequest;
use App\Http\Requests\UpdatePersonPhotoRequest;
use App\Http\Requests\UpdatePersonRequest;
use App\Models\Couple;
use App\Models\Person;
use App\Support\ClaimInvites;
use App\Support\ImageStore;
use Illuminate\Support\Collection;

class PersonController extends Controller
{
    public function show(Person $person)
    {
        $this->authorize('view', $person);

        $person->load(['profileFieldPrivacy', 'customFields', 'parents', 'children', 'records.media']);

        return view('people.show', ['person' => $person]);
    }

    public function createChild(Person $person)
    {
        $this->authorize('addChild', $person);

        // Each marriage, so the form can ask which spouse the child is from —
        // a man with several wives needs the mother named explicitly, or the
        // child's parentage is left ambiguous. Past marriages are offered too
        // (children outlive a divorce), labelled with their status.
        $possibleCoParents = $this->marriagesOf($person)
            ->map(fn (array $marriage) => [
                'person' => $marriage['partner'],
                'status' => $marriage['couple']->status,
            ])
            ->unique(fn (array $marriage) => $marriage['person']->id)
            ->values();

        $existingCandidates = auth()->user()->managesTree()
            ? Person::whereNotIn('id', $person->children->pluck('id')->push($person->id))->orderBy('full_name')->get()
            : collect();

        return view('people.create-child', [
            'parent' => $person,
            'possibleCoParents' => $possibleCoParents,
            'otherParentLabel' => $this->otherParentLabel($person),
            // With more than one spouse there's no sensible default, so the
            // choice becomes mandatory rather than "optional".
            'otherParentRequired' => $possibleCoParents->count() > 1,
            'existingCandidates' => $existingCandidates,
        ]);
    }

    /**
     * What to call the child's other parent on the add-child form, from the
     * perspective of the parent being added to.
     */
    private function otherParentLabel(Person $person): string
    {
        return match ($person->gender) {
            'male' => __('Mother'),
            'female' => __('Father'),
            default => __('Second Parent'),
        };
    }

    public function storeChild(StoreChildPersonRequest $request, Person $person)
    {
        if ($request->validated('mode') === 'existing') {
            $child = Person::findOrFail($request->validated('existing_person_id'));

            if ($child->isAncestorOf($person)) {
                return back()->withErrors(['existing_person_id' => 'That would make them their own ancestor — not allowed.'])->withInput();
            }
        } else {
            $path = ImageStore::put($request->file('photo'), 'profile-photos');

            $child = Person::create([
                'full_name' => $request->validated('full_name'),
                'email' => $request->validated('email'),
                'date_of_birth' => $request->validated('date_of_birth'),
                'gender' => $request->validated('gender'),
                'profile_photo_path' => $path,
                'created_by_person_id' => $request->user()->person?->id,
            ]);

            // A child under 18 is looked after by their parent and gets no
            // invitation; ClaimInvites decides that. An adult son or daughter
            // added here is invited straight away, same as anybody else.
            ClaimInvites::send($child, 'manual_invite', $request->user()->person);
        }

        $parentIds = [$person->id => ['relationship_type' => $request->validated('relationship_type')]];

        if ($coParentId = $request->validated('co_parent_id')) {
            $parentIds[$coParentId] = ['relationship_type' => $request->validated('relationship_type')];
        }

        $child->parents()->syncWithoutDetaching($parentIds);

        return redirect()->route('people.show', $child)->with('status', 'child-added');
    }

    public function edit(Person $person)
    {
        $this->authorize('update', $person);

        $person->load(['profileFieldPrivacy', 'customFields', 'parents', 'children']);

        return view('people.edit', [
            'person' => $person,
            'marriages' => $this->marriagesOf($person),
        ]);
    }

    /**
     * Every marriage this person is part of, from whichever side of the
     * couples table it happens to have been recorded on, as
     * `['couple' => Couple, 'partner' => Person]`.
     *
     * `toBase()` is load-bearing. Mapping an Eloquent collection to plain
     * arrays only downgrades it to a base collection when the result is
     * non-empty — on an empty one it stays an Eloquent collection, and
     * merging arrays into that makes it call getKey() on them.
     */
    private function marriagesOf(Person $person): Collection
    {
        $asPersonA = $person->marriagesAsA()->with('personB')->get()
            ->map(fn (Couple $couple) => ['couple' => $couple, 'partner' => $couple->personB])
            ->toBase();

        $asPersonB = $person->marriagesAsB()->with('personA')->get()
            ->map(fn (Couple $couple) => ['couple' => $couple, 'partner' => $couple->personA])
            ->toBase();

        return $asPersonA->merge($asPersonB)
            ->filter(fn (array $marriage) => $marriage['partner'] !== null)
            ->sortBy(fn (array $marriage) => $marriage['partner']->full_name)
            ->values();
    }

    public function update(UpdatePersonRequest $request, Person $person)
    {
        $data = $request->safe()->except(['field_privacy', 'is_deceased', 'death_date']);
        $data['is_deceased'] = $request->boolean('is_deceased');
        $data['death_date'] = $data['is_deceased'] ? $request->input('death_date') : null;

        $person->update($data);

        foreach ($request->input('field_privacy', []) as $fieldKey => $visibility) {
            $person->profileFieldPrivacy()->updateOrCreate(
                ['field_key' => $fieldKey],
                ['visibility' => $visibility]
            );
        }

        return redirect()->route('people.show', $person)->with('status', 'profile-updated');
    }

    public function updatePhoto(UpdatePersonPhotoRequest $request, Person $person)
    {
        $previousPath = $person->profile_photo_path;

        $path = ImageStore::put($request->file('photo'), 'profile-photos');

        $person->update(['profile_photo_path' => $path]);

        if ($previousPath) {
            ImageStore::delete($previousPath);
        }

        return back()->with('status', 'photo-updated');
    }
}
