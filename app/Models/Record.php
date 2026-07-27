<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Record extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'person_id',
        'type',
        'title',
        'description',
        'occurred_on',
        'meta',
        'visibility',
        'created_by_person_id',
    ];

    protected function casts(): array
    {
        return [
            'occurred_on' => 'date',
            'meta' => 'array',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'created_by_person_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(RecordMedia::class)->orderBy('sort_order');
    }
}
