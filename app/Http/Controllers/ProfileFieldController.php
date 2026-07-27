<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProfileFieldRequest;
use App\Http\Requests\UpdateProfileFieldRequest;
use App\Models\Person;
use App\Models\ProfileField;

class ProfileFieldController extends Controller
{
    public function store(StoreProfileFieldRequest $request, Person $person)
    {
        $field = $person->customFields()->create([
            ...$request->validated(),
            'sort_order' => $person->customFields()->count(),
        ]);

        if ($request->wantsJson()) {
            return response()->json($field);
        }

        return back()->with('status', 'field-added');
    }

    public function update(UpdateProfileFieldRequest $request, ProfileField $field)
    {
        $field->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json($field);
        }

        return back()->with('status', 'field-updated');
    }

    public function destroy(ProfileField $field)
    {
        $this->authorize('update', $field->person);

        $field->delete();

        if (request()->wantsJson()) {
            return response()->json(['deleted' => true]);
        }

        return back()->with('status', 'field-removed');
    }
}
