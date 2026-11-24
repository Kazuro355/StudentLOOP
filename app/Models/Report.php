<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    public const REASONS = [
        'Informasi barang tidak sesuai',
        'Barang atau konten tidak pantas',
        'Perilaku mencurigakan / penipuan',
        'Pengguna tidak merespons setelah pembayaran',
        'Lainnya',
    ];

    protected $fillable = ['reporter_id', 'target_type', 'target_id', 'reason', 'details', 'status'];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
