<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $fillable = [
        'email',
        'otp',
        'expires_at',
        'is_verified',
        'attempts',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'is_verified' => 'boolean',
    ];

    /**
     * Check whether this OTP record has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
