<?php

namespace App\Models;

use App\Support\ImageStore;
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

    /** Where this attachment is served from. */
    public function getUrlAttribute(): ?string
    {
        return ImageStore::url($this->path);
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(Record::class);
    }
}
