<?php

namespace Azeemade\BulkUpload\Models;

use Illuminate\Database\Eloquent\Model;

class BulkUpload extends Model
{
    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->morphTo();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed' || $this->status === 'partially_failed' || $this->status === 'failed';
    }
}
