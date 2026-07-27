<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecordMedia extends Model
{
    protected $table = 'record_media';

    protected $fillable = [
        'record_id',
        'path',
        'caption',
        'sort_order',
    ];

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class);
    }
}
