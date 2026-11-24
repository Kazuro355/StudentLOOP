<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Order extends Model
{
    /** Status yang mengunci tanggal sewa di kalender. */
    public const BLOCKING = ['menunggu_pembayaran', 'dibayar', 'diserahkan', 'pengembalian', 'menunggu_persetujuan_admin'];

    /** status => [label, kelas badge] */
    public const STATUS = [
        'menunggu_persetujuan' => ['Menunggu persetujuan pemilik', 'warn'],
        'menunggu_pembayaran'  => ['Menunggu pembayaran', 'warn'],
        'dibayar'              => ['Dibayar, menunggu serah terima', 'info'],
        'diserahkan'           => ['Berjalan', 'info'],
        'pengembalian'         => ['Menunggu konfirmasi pengembalian', 'warn'],
        'menunggu_persetujuan_admin' => ['Menunggu persetujuan pencairan admin', 'warn'],
        'selesai'              => ['Selesai', 'ok'],
        'ditolak'              => ['Ditolak pemilik', 'bad'],
        'dibatalkan'           => ['Dibatalkan', 'bad'],
    ];

    protected $fillable = [
        'code', 'item_id', 'buyer_id', 'seller_id', 'type', 'start_date', 'end_date', 'days',
        'price', 'subtotal', 'deposit', 'total', 'pickup_method', 'note', 'status',
        'handed_over_at', 'returned_at', 'completed_at', 'escrow_status',
        'funds_released_at', 'funds_released_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'handed_over_at' => 'datetime',
            'returned_at' => 'datetime',
            'completed_at' => 'datetime',
            'funds_released_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $o) {
            $o->code = $o->code ?: 'SL-' . now()->format('ymd') . '-' . strtoupper(Str::random(5));
        });
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest();
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function isRent(): bool
    {
        return $this->type === 'sewa';
    }

    public function statusLabel(): string
    {
        if ($this->status === 'diserahkan') {
            return $this->isRent() ? 'Sedang dipinjam' : 'Barang diserahkan, menunggu konfirmasi diterima';
        }
        return self::STATUS[$this->status][0] ?? $this->status;
    }

    public function statusClass(): string
    {
        return self::STATUS[$this->status][1] ?? 'info';
    }

    public function isActive(): bool
    {
        return ! in_array($this->status, ['selesai', 'ditolak', 'dibatalkan'], true);
    }

    public function partyOf(User $user): ?string
    {
        return match ($user->id) {
            $this->buyer_id => 'buyer',
            $this->seller_id => 'seller',
            default => null,
        };
    }

    public function otherParty(User $user): User
    {
        return $user->id === $this->buyer_id ? $this->seller : $this->buyer;
    }

    /** Langkah-langkah untuk timeline di halaman detail transaksi. */
    public function steps(): array
    {
        if ($this->isRent()) {
            return [
                'menunggu_persetujuan' => 'Pengajuan dikirim',
                'menunggu_pembayaran'  => 'Disetujui, menunggu pembayaran',
                'dibayar'              => 'Pembayaran diterima',
                'diserahkan'           => 'Barang diserahkan',
                'pengembalian'         => 'Barang dikembalikan',
                'menunggu_persetujuan_admin' => 'Menunggu persetujuan pencairan admin',
                'selesai'              => 'Dana dicairkan & transaksi selesai',
            ];
        }
        return [
            'menunggu_pembayaran' => 'Menunggu pembayaran',
            'dibayar'             => 'Pembayaran diterima',
            'diserahkan'          => 'Barang diserahkan',
            'selesai'             => 'Barang diterima, selesai',
        ];
    }
}
