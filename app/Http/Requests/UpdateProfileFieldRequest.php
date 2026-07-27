<?php

namespace App\Http\Requests;

use App\Models\ProfileField;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ProfileField $field */
        $field = $this->route('field');

        return $this->user()->can('update', $field->person);
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
