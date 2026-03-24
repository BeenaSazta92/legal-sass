<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToFirm;

class FirmSubscription extends Model
{
    use BelongsToFirm;
    public $timestamps = false;

    protected $fillable = [
        'firm_id',
        'subscription_id',
        'name',
        'max_admins',
        'max_lawyers',
        'max_clients',
        'max_documents_per_user',
        'started_at',
        'ended_at',
        'is_default_assigned'
    ];

    public function firm()
    {
        return $this->belongsTo(LawFirm::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    // Create snapshot from template
    public static function createFromTemplate(LawFirm $firm, Subscription $subscription, bool $isDefault = false)
    {
        
        if ($firm->currentSubscription) {
            $firm->currentSubscription->update(['ended_at' => now()]);
        }
        return self::create([
            'firm_id' => $firm->id,
            'subscription_id' => $subscription->id,
            'name' => $subscription->name,
            'max_admins' => $subscription->max_admins,
            'max_lawyers' => $subscription->max_lawyers,
            'max_clients' => $subscription->max_clients,
            'max_documents_per_user' => $subscription->max_documents_per_user,
            'started_at' => now(),
            'is_default_assigned' => $isDefault
        ]);
    }
}
