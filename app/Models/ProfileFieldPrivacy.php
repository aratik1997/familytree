<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileFieldPrivacy extends Model
{
    protected $table = 'profile_field_privacies';

    protected $fillable = [
        'person_id',
        'field_key',
        'visibility',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
