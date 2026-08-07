<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTree;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Couple extends Model
{
    use BelongsToTree;

    protected $fillable = [
        'tree_id',
        'person_a_id',
        'person_b_id',
        'status',
        'started_on',
        'ended_on',
    ];

    protected function casts(): array
    {
        return [
            'started_on' => 'date',
            'ended_on' => 'date',
        ];
    }

    public function personA(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_a_id');
    }

    public function personB(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_b_id');
    }
}
