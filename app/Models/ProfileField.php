<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileField extends Model
{
    protected $fillable = [
        'person_id',
        'label',
        'field_type',
        'value',
        'visibility',
        'sort_order',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
