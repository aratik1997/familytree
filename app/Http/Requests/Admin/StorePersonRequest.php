<?php

namespace App\Http\Requests\Admin;

use App\Models\Person;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_super_admin;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female'],
            'photo' => ['required', 'image', 'max:4096'],

            // Who this new person is a child of — mandatory, since every
            // person added this way should be wired into the tree right
            // away rather than left as a floating orphan node. Either a
            // plain person id, or "couple:idA-idB" for both spouses at once.
            'parent_selection' => ['required', 'string'],
            'parent_relationship_type' => ['required', 'in:biological,step,adoptive,guardian'],

            // Optionally, an existing person this new person is a parent of.
            'child_person_id' => ['nullable', 'integer', 'exists:people,id'],
            'child_relationship_type' => ['required_with:child_person_id', 'nullable', 'in:biological,step,adoptive,guardian'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $value = (string) $this->input('parent_selection');

            if (str_starts_with($value, 'couple:')) {
                $ids = explode('-', substr($value, 7));
                if (count($ids) !== 2 || Person::whereIn('id', $ids)->count() !== 2) {
                    $validator->errors()->add('parent_selection', 'Select a valid person or couple.');
                }
            } elseif (! Person::whereKey($value)->exists()) {
                $validator->errors()->add('parent_selection', 'Select a valid person or couple.');
            }
        });
    }
}
