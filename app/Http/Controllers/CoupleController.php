<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCoupleRequest;
use App\Models\Couple;

class CoupleController extends Controller
{
    /**
     * Records how a marriage stands now — still married, divorced, separated,
     * widowed or partnered. The tree draws each differently, so this is what
     * makes a divorce show up as a broken line rather than a solid one.
     */
    public function update(UpdateCoupleRequest $request, Couple $couple)
    {
        $status = $request->validated('status');

        $couple->update([
            'status' => $status,
            // A marriage that is still current can't have an end date; one
            // that has ended keeps whatever date was given, if any.
            'ended_on' => in_array($status, ['married', 'partnered'], true)
                ? null
                : $request->validated('ended_on'),
        ]);

        return back()->with('status', 'marriage-updated');
    }
}
