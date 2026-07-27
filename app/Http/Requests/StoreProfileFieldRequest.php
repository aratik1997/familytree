<?php

namespace App\Http\Requests;

use App\Models\Person;
use Illuminate\Foundation\Http\FormRequest;

class StoreProfileFieldRequest extends FormRequest
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
            'label' => ['required', 'string', 'max:100'],
            'field_type' => ['required', 'in:text,textarea,date,url,number'],
            'value' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', 'in:everyone,family,private'],
        ];
    }
}
