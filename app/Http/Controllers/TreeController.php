<?php

namespace App\Http\Controllers;

use App\Http\Resources\PersonNodeResource;
use App\Models\Couple;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TreeController extends Controller
{
    public function index()
    {
        return view('tree.index');
    }

    /**
     * JSON edge/node list for the 2D (D3) and 3D (Three.js) tree views.
     * Built directly from person_parent/couples rather than N Eloquent
     * relationship calls, to keep this a small, fixed number of queries
     * regardless of tree size.
     */
    public function data()
    {
        $people = Person::with('profileFieldPrivacy')->get();

        $nodes = PersonNodeResource::collection($people)->resolve();

        $parentEdges = DB::table('person_parent')
            ->get()
            ->map(fn ($row) => [
                'source' => $row->parent_id,
                'target' => $row->child_id,
                'type' => 'parent',
                'relationship_type' => $row->relationship_type,
            ]);

        $spouseEdges = Couple::all()->map(fn (Couple $couple) => [
            'source' => $couple->person_a_id,
            'target' => $couple->person_b_id,
            'type' => 'spouse',
            'status' => $couple->status,
        ]);

        return response()->json([
            'nodes' => $nodes,
            'edges' => $parentEdges->merge($spouseEdges)->values(),
        ]);
    }

    /**
     * Saves a manually dragged position for one person, overriding the
     * auto-computed layout for them from now on.
     */
    public function updatePosition(Request $request, Person $person)
    {
        $validated = $request->validate([
            'x' => ['required', 'integer'],
            'y' => ['required', 'integer'],
        ]);

        $person->update([
            'tree_pos_x' => $validated['x'],
            'tree_pos_y' => $validated['y'],
        ]);

        return response()->json(['message' => 'Saved.']);
    }

    /**
     * Clears every manually dragged position, reverting everyone back to
     * the auto-computed layout. Doesn't touch any person/relationship data.
     */
    public function resetPositions()
    {
        Person::whereNotNull('tree_pos_x')->orWhereNotNull('tree_pos_y')
            ->update(['tree_pos_x' => null, 'tree_pos_y' => null]);

        return response()->json(['message' => 'Reset.']);
    }
}
