<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;
    public $timestamps = false;

    protected $fillable = [
        'date_added', 'contractor', 'project_name', 'value',
        'status', 'is_priority', 'encoded_by', 'created_at',
    ];

    protected $casts = [
        'date_added' => 'date',
        'value' => 'decimal:2',
        'is_priority' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function encoder()
    {
        return $this->belongsTo(User::class, 'encoded_by');
    }
}
