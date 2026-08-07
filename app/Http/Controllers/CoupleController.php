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

    /**
     * Takes a marriage off the record entirely.
     *
     * Distinct from marking it divorced, which is the usual thing to want: a
     * divorce belongs in the record and is drawn as a broken line. This is for
     * a marriage that should never have been entered at all, and it is the only
     * way to undo one — until now there was none, so a mistaken couple stayed
     * on the chart for good.
     */
    public function destroy(Couple $couple)
    {
        $couple->delete();

        return back()->with('status', 'marriage-removed');
    }
}
