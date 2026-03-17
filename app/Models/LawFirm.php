<?php

namespace App\Models;

use Database\Factories\LawFirmFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LawFirm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'subscription_id',
        'status',
    ];

    protected static function newFactory()
    {
        return LawFirmFactory::new();
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'firm_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'firm_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }
}
