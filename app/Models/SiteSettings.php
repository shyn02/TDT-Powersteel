<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSettings extends Model
{
    public $timestamps = false; // updated_at handled manually below

    protected $fillable = [
        'site_name', 'support_email', 'support_phone', 'company_address',
        'session_timeout_minutes', 'max_login_attempts', 'lockout_minutes',
        'require_strong_passwords', 'notify_new_quote', 'notify_new_referral',
        'notify_new_chat', 'notification_email', 'timezone_name', 'currency',
        'date_format', 'maintenance_mode', 'maintenance_message',
        'updated_at', 'updated_by',
    ];

    protected $casts = [
        'require_strong_passwords' => 'boolean',
        'notify_new_quote' => 'boolean',
        'notify_new_referral' => 'boolean',
        'notify_new_chat' => 'boolean',
        'maintenance_mode' => 'boolean',
        'updated_at' => 'datetime',
    ];

    /** Singleton accessor — mirrors Django's SiteSettings.get_solo(). */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
