<?php

namespace App\Http\Requests;

use App\Models\Person;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePersonRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'url', 'max:255'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'gender' => ['nullable', 'in:male,female'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'is_deceased' => ['nullable', 'boolean'],
            'death_date' => ['nullable', 'date', 'required_if:is_deceased,1'],
            'field_privacy' => ['nullable', 'array'],
            'field_privacy.*' => ['in:everyone,family,private'],
        ];
    }
}
