<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'full_name', 'company_name', 'email', 'phone', 'landline',
        'address', 'how_heard', 'message', 'status', 'is_seen', 'created_at',
    ];

    protected $casts = [
        'is_seen' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function getDisplayNameAttribute(): string
    {
        return $this->company_name
            ? "{$this->full_name} ({$this->company_name})"
            : $this->full_name;
    }
}
