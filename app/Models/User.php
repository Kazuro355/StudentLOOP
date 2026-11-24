<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const JURUSAN = [
        'Cyber Security', 'Computer Science', 'Information Systems', 'Software Engineering',
        'Data Science', 'Mobile Application & Technology', 'Game Application & Technology',
        'Teknik Industri', 'Manajemen', 'Akuntansi', 'Desain Komunikasi Visual', 'Lainnya',
    ];

    protected $fillable = [
        'name', 'email', 'password', 'nim', 'jurusan', 'angkatan', 'kampus',
        'phone', 'area', 'bio', 'profile_completed_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'profile_completed_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function isProfileComplete(): bool
    {
        return $this->profile_completed_at !== null;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name));
        return strtoupper(mb_substr($parts[0] ?? '?', 0, 1) . mb_substr($parts[1] ?? '', 0, 1));
    }

    public function firstName(): string
    {
        return explode(' ', trim($this->name))[0];
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'favorites')->withTimestamps();
    }

    public function blockedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_blocks', 'user_id', 'blocked_id')->withTimestamps();
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function reviewsReceived(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewee_id');
    }

    public function hasBlocked(User $other): bool
    {
        return $this->blockedUsers()->where('blocked_id', $other->id)->exists();
    }

    /** ID semua pengguna yang tidak boleh berinteraksi dengan user ini (diblokir / memblokir). */
    public function blockedIds(): array
    {
        $mine = $this->blockedUsers()->pluck('users.id')->all();
        $theirs = \DB::table('user_blocks')->where('blocked_id', $this->id)->pluck('user_id')->all();
        return array_values(array_unique(array_merge($mine, $theirs)));
    }

    public function ratingAvg(): ?float
    {
        $avg = $this->reviewsReceived()->avg('rating');
        return $avg ? round($avg, 1) : null;
    }

    public function unreadMessagesCount(): int
    {
        return Message::query()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $this->id)
            ->whereHas('conversation', function ($q) {
                $q->where('user_one_id', $this->id)->orWhere('user_two_id', $this->id);
            })
            ->count();
    }
}
