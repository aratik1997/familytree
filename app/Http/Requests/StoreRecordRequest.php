<?php

namespace App\Http\Requests;

use App\Models\Person;
use App\Models\Record;
use Illuminate\Foundation\Http\FormRequest;

class StoreRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Person $person */
        $person = $this->route('person');

        return $this->user()->can('create', [Record::class, $person]);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:academic,photo,moment,career,other'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'occurred_on' => ['nullable', 'date'],
            'visibility' => ['required', 'in:everyone,family,private'],
            'meta' => ['nullable', 'array'],
            'meta.*' => ['nullable', 'string', 'max:255'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:4096'],
        ];
    }
}
