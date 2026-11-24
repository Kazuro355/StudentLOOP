<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPhoto extends Model
{
    protected $fillable = ['order_id', 'uploaded_by', 'type', 'path'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        $version = $this->updated_at ? $this->updated_at->timestamp : $this->id;
        return '/storage/' . ltrim($this->path, '/') . '?v=' . $version;
    }
}
