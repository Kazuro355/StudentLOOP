<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WantedOffer extends Model
{
    protected $fillable = ['wanted_request_id', 'user_id', 'item_id', 'message', 'price', 'status'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(WantedRequest::class, 'wanted_request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
