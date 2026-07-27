<?php

namespace App\Http\Requests;

use App\Models\Couple;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateCoupleRequest extends FormRequest
{
    /**
     * Whoever may edit either partner's profile may record how their marriage
     * stands — it is as much one person's record as the other's.
     */
    public function authorize(): bool
    {
        /** @var Couple $couple */
        $couple = $this->route('couple');

        return ($couple->personA && $this->user()->can('update', $couple->personA))
            || ($couple->personB && $this->user()->can('update', $couple->personB));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:married,divorced,separated,widowed,partnered'],
            'ended_on' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Couple $couple */
            $couple = $this->route('couple');
            $endedOn = $this->input('ended_on');

            if (! $endedOn || ! $couple->started_on) {
                return;
            }

            if (strtotime($endedOn) < $couple->started_on->timestamp) {
                $validator->errors()->add(
                    'ended_on',
                    'That end date is before the marriage began. Check the year.'
                );
            }
        });
    }
}
