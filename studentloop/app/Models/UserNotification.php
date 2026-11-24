<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotification extends Model
{
    public const TYPES = [
        'transaksi' => 'Transaksi', 'pembayaran' => 'Pembayaran', 'pengembalian' => 'Pengembalian',
        'ulasan' => 'Ulasan', 'wanted' => 'Wanted', 'keamanan' => 'Keamanan',
    ];

    protected $fillable = ['user_id', 'type', 'title', 'body', 'url', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Buat notifikasi untuk seorang pengguna. */
    public static function createFor(User|int $user, string $type, string $title, ?string $body = null, ?string $url = null): self
    {
        return static::create([
            'user_id' => $user instanceof User ? $user->id : $user,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'url' => $url,
        ]);
    }
}
