<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSettings extends Model
{
    public $timestamps = false;

    protected $fillable = ['max_active_chats_per_rep', 'updated_at', 'updated_by'];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    /** Singleton accessor — mirrors Django's SystemSettings.get_solo(). */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
