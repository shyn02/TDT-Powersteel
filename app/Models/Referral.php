<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Referral extends Model
{
    use SoftDeletes;
    public $timestamps = false;

    protected $fillable = [
        'referrer_name', 'referrer_company', 'referrer_phone', 'referrer_email',
        'contact_person', 'referred_company', 'project_type', 'project_scale',
        'region', 'remarks', 'status', 'is_seen', 'created_at',
    ];

    protected $casts = [
        'is_seen' => 'boolean',
        'created_at' => 'datetime',
    ];
}
