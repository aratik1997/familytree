<?php

namespace App\Http\Requests;

use App\Models\Record;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Record $record */
        $record = $this->route('record');

        return $this->user()->can('update', $record);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'occurred_on' => ['nullable', 'date'],
            'visibility' => ['required', 'in:everyone,family,private'],
            'meta' => ['nullable', 'array'],
            'meta.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
