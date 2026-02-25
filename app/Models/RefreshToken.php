<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
class RefreshToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public static function createForUser(User $user): self
    {
        // Remove old refresh tokens for this user
        self::where('user_id', $user->id)->delete();

        return self::create([
            'user_id' => $user->id,
            'token' => self::generateToken(),
            'expires_at' => Carbon::now()->addDays(30), // 30 days expiry
        ]);
    }
}
