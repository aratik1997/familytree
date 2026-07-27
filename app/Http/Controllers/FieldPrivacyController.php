<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateFieldPrivacyRequest;
use App\Models\Person;

class FieldPrivacyController extends Controller
{
    public function update(UpdateFieldPrivacyRequest $request, Person $person, string $fieldKey)
    {
        $person->profileFieldPrivacy()->updateOrCreate(
            ['field_key' => $fieldKey],
            ['visibility' => $request->validated('visibility')]
        );

        if ($request->wantsJson()) {
            return response()->json(['field_key' => $fieldKey, 'visibility' => $request->validated('visibility')]);
        }

        return back()->with('status', 'privacy-updated');
    }
}
