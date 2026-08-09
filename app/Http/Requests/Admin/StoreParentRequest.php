<?php

namespace App\Http\Requests\Admin;

use App\Models\Person;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreParentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canManageTree();
    }

    public function rules(): array
    {
        return [
            'mode' => ['required', 'in:new,existing'],
            // Either a plain person id, or "couple:idA-idB" when both
            // members of an existing couple were picked in one go.
            'existing_person_id' => ['required_if:mode,existing', 'nullable', 'string'],
            'full_name' => ['required_if:mode,new', 'nullable', 'string', 'max:255'],
            'email' => ['required_if:mode,new', 'nullable', 'email', 'max:255'],
            'date_of_birth' => ['required_if:mode,new', 'nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female'],
            'photo' => ['required_if:mode,new', 'nullable', 'image', 'max:4096'],
            'relationship_type' => ['required', 'in:biological,step,adoptive,guardian'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('mode') !== 'existing' || $validator->errors()->isNotEmpty()) {
                return;
            }

            $value = (string) $this->input('existing_person_id');

            if (str_starts_with($value, 'couple:')) {
                $ids = explode('-', substr($value, 7));
                if (count($ids) !== 2 || Person::whereIn('id', $ids)->count() !== 2) {
                    $validator->errors()->add('existing_person_id', 'Select a valid person or couple.');
                }
            } elseif (! Person::whereKey($value)->exists()) {
                $validator->errors()->add('existing_person_id', 'Select a valid person or couple.');
            }
        });
    }
}
