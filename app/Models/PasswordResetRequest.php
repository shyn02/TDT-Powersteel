<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordResetRequest extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email', 'user_id', 'status', 'requested_at', 'expires_at', 'resolved_at', 'resolved_by',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'expires_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->expires_at)) {
                $model->expires_at = now()->addHours(24);
            }
        });
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
