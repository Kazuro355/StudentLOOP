<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const METHODS = ['qris' => 'QRIS', 'ewallet' => 'E-wallet', 'bank' => 'Transfer bank'];
    public const EWALLETS = ['gopay' => 'GoPay', 'ovo' => 'OVO', 'dana' => 'DANA', 'shopeepay' => 'ShopeePay'];
    /** kode => [nama, prefix virtual account] */
    public const BANKS = ['bca' => ['BCA', '39201'], 'mandiri' => ['Mandiri', '88608'], 'bni' => ['BNI', '8277'], 'bri' => ['BRI', '26215']];

    protected $fillable = [
        'order_id', 'method', 'provider', 'account_number', 'phone', 'reference',
        'amount', 'status', 'expires_at', 'paid_at',
    ];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'paid_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function methodLabel(): string
    {
        $label = self::METHODS[$this->method] ?? $this->method;
        if ($this->method === 'ewallet') {
            return $label . ' · ' . (self::EWALLETS[$this->provider] ?? $this->provider);
        }
        if ($this->method === 'bank') {
            return $label . ' · ' . (self::BANKS[$this->provider][0] ?? $this->provider);
        }
        return $label;
    }

    public function isExpired(): bool
    {
        return $this->status === 'pending' && $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Matriks QR dekoratif (demo) yang deterministik dari nomor referensi.
     * Ganti dengan QR sungguhan dari payment gateway saat produksi.
     */
    public function qrCells(int $n = 25): array
    {
        $seed = crc32($this->reference) & 0x7fffffff;
        $cells = [];
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                $inFinder = ($x < 8 && $y < 8) || ($x >= $n - 8 && $y < 8) || ($x < 8 && $y >= $n - 8);
                if ($inFinder) {
                    $lx = $x < 8 ? $x : $x - ($n - 8);
                    $ly = $y < 8 ? $y : $y - ($n - 8);
                    $cells[$y][$x] = $lx <= 6 && $ly <= 6 && (
                        $lx === 0 || $lx === 6 || $ly === 0 || $ly === 6 || ($lx >= 2 && $lx <= 4 && $ly >= 2 && $ly <= 4)
                    );
                } else {
                    $seed = ($seed * 1103515245 + 12345) & 0x7fffffff;
                    $cells[$y][$x] = (($seed >> 16) & 1) === 1;
                }
            }
        }
        return $cells;
    }
}
