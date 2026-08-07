<?php

namespace App\Http\Requests;

use App\Models\Person;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreChildPersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Person $person */
        $person = $this->route('person');

        return $this->user()->can('addChild', $person);
    }

    public function rules(): array
    {
        return [
            'mode' => ['nullable', 'in:new,existing'],
            'existing_person_id' => ['required_if:mode,existing', 'nullable', 'integer', 'exists:people,id'],
            'full_name' => ['required_if:mode,new', 'nullable', 'string', 'max:255'],
            'email' => ['required_if:mode,new', 'nullable', 'email', 'max:255'],
            'date_of_birth' => ['required_if:mode,new', 'nullable', 'date', 'before:today'],
            'gender' => ['nullable', 'in:male,female'],
            'photo' => ['required_if:mode,new', 'nullable', 'image', 'max:4096'],
            'relationship_type' => ['required', 'in:biological,step,adoptive,guardian'],
            'co_parent_id' => [
                // With several spouses on record there's no way to guess the
                // other parent, so naming them stops being optional.
                $this->parentHasMultipleSpouses() ? 'required' : 'nullable',
                'integer',
                'exists:people,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'co_parent_id.required' => 'Please choose which spouse this child is from.',
        ];
    }

    private function parentHasMultipleSpouses(): bool
    {
        /** @var Person $person */
        $person = $this->route('person');

        return $person->spouses()->count() > 1;
    }

    /**
     * Linking an already-existing person as a child (rather than creating a
     * new one) is a Super Admin–only power — anyone else can only add
     * brand-new children, matching the historical behavior of this form.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('mode') === 'existing' && ! $this->user()->managesTree()) {
                $validator->errors()->add('mode', 'Only a Super Admin can link an existing person as a child.');
            }

            // The form only ever offers this parent's own spouses; anything
            // else arriving here would quietly record a co-parent who was
            // never married to them.
            $coParentId = $this->input('co_parent_id');
            if ($coParentId) {
                /** @var Person $person */
                $person = $this->route('person');

                if (! $person->spouses()->contains('id', (int) $coParentId)) {
                    $validator->errors()->add('co_parent_id', 'That person is not recorded as a spouse.');
                }
            }
        });
    }
}
