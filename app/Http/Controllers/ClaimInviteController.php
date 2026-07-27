<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClaimAccountRequest;
use App\Models\ClaimInvite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ClaimInviteController extends Controller
{
    public function show(string $token)
    {
        $invite = $this->findValidInvite($token);

        return view('claim.show', ['invite' => $invite, 'person' => $invite->person, 'token' => $token]);
    }

    public function store(ClaimAccountRequest $request, string $token)
    {
        $invite = $this->findValidInvite($token);
        $person = $invite->person;

        $user = User::create([
            'name' => $person->full_name,
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        $person->update([
            'user_id' => $user->id,
            'claim_status' => 'claimed',
            'claimed_at' => now(),
        ]);

        $invite->update(['used_at' => now()]);

        Auth::login($user);

        return redirect()->route('dashboard')->with('status', 'account-claimed');
    }

    private function findValidInvite(string $token): ClaimInvite
    {
        $invite = ClaimInvite::with('person')
            ->where('token', hash('sha256', $token))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        abort_if($invite->person->user_id !== null, 404);

        return $invite;
    }
}
