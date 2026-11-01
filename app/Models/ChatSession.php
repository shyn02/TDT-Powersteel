<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatSession extends Model
{
    use SoftDeletes;
    public $timestamps = false;

    protected $fillable = [
        'session_token', 'client_name', 'page', 'is_active', 'created_at',
        'last_message_at', 'last_activity_at', 'assigned_to', 'status', 'is_priority',
        'token_version', 'revoked_at', 'expires_at',
        'terminated_at', 'terminated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_priority' => 'boolean',
        'created_at' => 'datetime',
        'last_message_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'revoked_at' => 'datetime',
        'expires_at' => 'datetime',
        'terminated_at' => 'datetime',
    ];

    public function assignedRep()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function terminator()
    {
        return $this->belongsTo(User::class, 'terminated_by');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'session_id');
    }

    public function getUnreadCountAttribute(): int
    {
        return $this->messages()->where('sender', 'client')->where('is_read', false)->count();
    }

    /**
     * Derived lifecycle state with exact terminal precedence.
     *
     * terminated_at != null wins first; otherwise revoked_at != null is
     * invalid; otherwise now >= expires_at is expired; otherwise active.
     * is_active and assignment status never override these predicates.
     * A null expires_at is treated as active (expiry not proven).
     */
    public function lifecycleStatus(CarbonInterface $now): string
    {
        if ($this->terminated_at !== null) {
            return 'terminated';
        }

        if ($this->revoked_at !== null) {
            return 'invalid';
        }

        if ($this->expires_at !== null && $now->greaterThanOrEqualTo($this->expires_at)) {
            return 'expired';
        }

        return 'active';
    }

    public function isGuestWritable(CarbonInterface $now): bool
    {
        return $this->lifecycleStatus($now) === 'active';
    }
}
