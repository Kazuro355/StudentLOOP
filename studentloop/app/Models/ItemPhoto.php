<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemPhoto extends Model
{
    protected $fillable = ['item_id', 'path', 'position'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function getUrlAttribute(): string
    {
        $version = $this->updated_at ? $this->updated_at->timestamp : $this->id;
        return '/storage/' . ltrim($this->path, '/') . '?v=' . $version;
    }
}
