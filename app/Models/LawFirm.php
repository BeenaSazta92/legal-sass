<?php

namespace App\Models;

use Database\Factories\LawFirmFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\{FirmSubscription,AppSetting};
use Illuminate\Support\Facades\DB;
use App\Models\Traits\AuditLogs;

class LawFirm extends Model
{
    use HasFactory, SoftDeletes,AuditLogs;
     /**
     * Allowed user roles
     */
    public const STATUS = [
        'active',
        'suspended',
    ];
    protected $attributes = [
        'status' => 'active',
    ];
    protected $fillable = [
        'name',
        'current_subscription_id',
        'status'
    ];

    protected static function newFactory()
    {
        return LawFirmFactory::new();
    }

    public function currentSubscription(){
        return $this->belongsTo(FirmSubscription::class, 'current_subscription_id');
    }

    // public function subscription()
    // {
    //     return $this->belongsTo(Subscription::class);
    // }

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


     // Assign default subscription
    public function assignDefaultSubscription()
    {
        $default = Subscription::getDefault();
        $snapshot = FirmSubscription::createFromTemplate($this, $default);
        $this->update(['current_subscription_id' => $snapshot->id ]);
    }

     // Upgrade/downgrade

    public function changeSubscription(Subscription $subscription)
    {
        return DB::transaction(function () use ($subscription) {
            $snapshot = FirmSubscription::createFromTemplate($this, $subscription);
            $this->update(['current_subscription_id' => $snapshot->id]);
            return $snapshot;
        });
    }

    public static function getDefault()
    {
        
        $defaultId = AppSetting::getSetting('default_subscription_id');

        if (!$defaultId) {
            throw new \Exception('Default subscription not configured');
        }
        return self::findOrFail($defaultId);
    }

    protected static function booted()
    {
        static::saving(function ($firm) {
            if (!in_array($firm->status, self::STATUS)) {
                throw new \InvalidArgumentException('Invalid status. Allowed: ' . implode(', ', self::STATUS));
            }
        });
    }
}
