<?php

namespace App\Http\Requests\Admin;

use App\Models\Person;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSpouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->managesTree();
    }

    public function rules(): array
    {
        return [
            'mode' => ['required', 'in:new,existing'],
            'existing_person_id' => ['required_if:mode,existing', 'nullable', 'integer', 'exists:people,id'],
            'full_name' => ['required_if:mode,new', 'nullable', 'string', 'max:255'],
            'email' => ['required_if:mode,new', 'nullable', 'email', 'max:255'],
            'date_of_birth' => ['required_if:mode,new', 'nullable', 'date', 'before:today'],
            'gender' => ['required_if:mode,new', 'nullable', 'in:male,female'],
            'photo' => ['required_if:mode,new', 'nullable', 'image', 'max:4096'],
            'status' => ['required', 'in:married,divorced,separated,widowed,partnered'],
            'started_on' => ['nullable', 'date'],
            'ended_on' => ['nullable', 'date', 'after_or_equal:started_on'],
        ];
    }

    /**
     * Spouses must be opposite gender, matching how this tree models
     * marriage. Both people need a gender on file to check that at all.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Person $person */
            $person = $this->route('person');

            $spouseGender = $this->input('mode') === 'existing'
                ? Person::find($this->input('existing_person_id'))?->gender
                : $this->input('gender');

            if (! $person->gender || ! $spouseGender) {
                $validator->errors()->add('gender', 'Both people need a gender on file before they can be linked as spouses.');

                return;
            }

            if ($person->gender === $spouseGender) {
                $validator->errors()->add('gender', 'A spouse must be the opposite gender.');
            }
        });
    }
}
