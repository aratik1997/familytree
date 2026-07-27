<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ClaimAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Token validity (exists, unused, unexpired) is checked in the
        // controller, not here — there's no authenticated user yet to check
        // against on this guest-only route.
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
