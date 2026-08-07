<?php

namespace App\Http\Controllers;

use App\Models\Tree;
use App\Models\User;
use App\Support\AdminAccounts;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The Super Admin's one exclusive job: saying who the Admins are.
 *
 * Everything else an Admin can do, the Super Admin can do too — but only in
 * their own tree. This is the sole thing not shared.
 */
class AdminUserController extends Controller
{
    public function index()
    {
        $admins = User::where('is_admin', true)
            ->with('tree')
            ->withCount('person')
            ->orderBy('name')
            ->get();

        return view('admin.admins.index', ['admins' => $admins]);
    }

    public function create()
    {
        return view('admin.admins.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'tree_name' => ['nullable', 'string', 'max:255'],
        ]);

        AdminAccounts::create(
            $validated['name'],
            $validated['email'],
            $validated['tree_name'] ?? null,
        );

        return redirect()->route('admin.admins.index')->with('status', 'admin-created');
    }

    public function edit(User $admin)
    {
        abort_unless($admin->is_admin, 404);

        return view('admin.admins.edit', ['admin' => $admin]);
    }

    public function update(Request $request, User $admin)
    {
        abort_unless($admin->is_admin, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($admin->id)],
            'tree_name' => ['required', 'string', 'max:255'],
        ]);

        $admin->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $admin->tree?->update(['name' => $validated['tree_name']]);

        return redirect()->route('admin.admins.index')->with('status', 'admin-updated');
    }

    /** Sends the claim link again, for one that bounced or was never opened. */
    public function resendInvite(User $admin)
    {
        abort_unless($admin->is_admin, 404);

        AdminAccounts::invite($admin);

        return back()->with('status', 'admin-invited');
    }

    /**
     * Removes the Admin and, with them, their tree and everybody in it.
     *
     * Their family's records exist only inside that tree — nobody else can see
     * them, so there is nowhere for them to go. The confirmation says so
     * plainly, and the count of people is shown next to it.
     */
    public function destroy(User $admin)
    {
        abort_unless($admin->is_admin, 404);

        $tree = $admin->tree;

        $admin->delete();
        $tree?->delete();

        return redirect()->route('admin.admins.index')->with('status', 'admin-removed');
    }

    /** How many people are in a tree, ignoring whose tree the viewer is in. */
    public static function peopleIn(?Tree $tree): int
    {
        return $tree ? $tree->people()->acrossAllTrees()->where('tree_id', $tree->id)->count() : 0;
    }
}
