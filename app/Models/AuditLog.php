<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'firm_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function firm()
    {
        return $this->belongsTo(LawFirm::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
