<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    public const TYPES = ['beli' => 'Beli', 'sewa' => 'Sewa'];
    public const CONDITIONS = ['Baru', 'Seperti baru', 'Baik', 'Cukup'];
    public const CAMPUSES = ['Kemanggisan', 'Alam Sutera', 'Syahdan', 'Bekasi', 'Malang'];
    public const PICKUP = ['ambil_kampus' => 'Ambil di kampus', 'pengiriman' => 'Pengiriman'];

    protected $fillable = [
        'user_id', 'category_id', 'type', 'title', 'description', 'condition', 'kelengkapan',
        'price', 'deposit', 'min_days', 'max_days', 'pickup_method', 'meeting_point',
        'kampus', 'status', 'moderated_at',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ItemPhoto::class)->orderBy('position');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ItemQuestion::class)->latest();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function favoritedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    /* ---------- scopes ---------- */

    public function scopeBrowsable(Builder $q): Builder
    {
        return $q->where('status', 'tersedia')
            ->whereNull('moderated_at')
            ->whereHas('owner', fn (Builder $owner) => $owner->whereNull('suspended_at'));
    }

    /** Sembunyikan barang dari pengguna yang saling memblokir. */
    public function scopeVisibleTo(Builder $q, ?User $user): Builder
    {
        if (! $user) {
            return $q;
        }
        $blocked = $user->blockedIds();
        return $blocked ? $q->whereNotIn('user_id', $blocked) : $q;
    }

    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        if (! $term) {
            return $q;
        }
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
        return $q->where(function ($w) use ($like) {
            $w->where('title', 'like', $like)
              ->orWhere('description', 'like', $like)
              ->orWhere('kampus', 'like', $like)
              ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like));
        });
    }

    /* ---------- accessors ---------- */

    public function getCoverUrlAttribute(): ?string
    {
        $photo = $this->relationLoaded('photos') ? $this->photos->first() : $this->photos()->first();
        return $photo?->url;
    }

    public function getPriceLabelAttribute(): string
    {
        return 'Rp' . number_format($this->price, 0, ',', '.') . ($this->type === 'sewa' ? '/hari' : '');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function isRent(): bool
    {
        return $this->type === 'sewa';
    }

    public function ratingAvg(): ?float
    {
        $avg = Review::where('item_id', $this->id)->where('reviewee_id', $this->user_id)->avg('rating');
        return $avg ? round($avg, 1) : null;
    }

    /** Rentang tanggal yang sudah terpakai (untuk kalender pilih tanggal sewa). */
    public function bookedRanges(): array
    {
        return $this->orders()
            ->where('type', 'sewa')
            ->whereIn('status', Order::BLOCKING)
            ->get(['start_date', 'end_date'])
            ->map(fn ($o) => [$o->start_date->toDateString(), $o->end_date->toDateString()])
            ->all();
    }

    public function isBookedBetween(string $start, string $end, ?int $ignoreOrderId = null): bool
    {
        return $this->orders()
            ->where('type', 'sewa')
            ->whereIn('status', Order::BLOCKING)
            ->when($ignoreOrderId, fn ($q) => $q->where('id', '!=', $ignoreOrderId))
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->exists();
    }
}
