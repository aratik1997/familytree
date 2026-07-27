<?php

namespace App\Http\Requests;

use App\Models\Person;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFieldPrivacyRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Person $person */
        $person = $this->route('person');

        return $this->user()->can('update', $person);
    }

    public function rules(): array
    {
        return [
            'visibility' => ['required', 'in:everyone,family,private'],
        ];
    }
}
