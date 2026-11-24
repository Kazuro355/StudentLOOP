<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WantedRequest extends Model
{
    protected $fillable = [
        'user_id', 'category_id', 'title', 'details', 'mode', 'budget_min', 'budget_max',
        'needed_by', 'kampus', 'status',
    ];

    protected function casts(): array
    {
        return ['needed_by' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(WantedOffer::class)->latest();
    }

    public function budgetLabel(): string
    {
        $f = fn ($n) => 'Rp' . number_format($n, 0, ',', '.');
        return match (true) {
            $this->budget_min && $this->budget_max => $f($this->budget_min) . ' – ' . $f($this->budget_max),
            (bool) $this->budget_max => 'Maks. ' . $f($this->budget_max),
            (bool) $this->budget_min => 'Mulai ' . $f($this->budget_min),
            default => 'Budget fleksibel',
        };
    }
}
