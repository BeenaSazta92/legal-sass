<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\LawFirm;
use App\Models\Subscription;
use App\Models\FirmSubscription;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * Change the default subscription for new firms (Platform-level)
     *
     * @param int $newDefaultId
     * @return void
     */
    public function changeDefault(int $newDefaultId): void
    {
        DB::transaction(function () use ($newDefaultId) {
            AppSetting::setSetting('default_subscription_id', $newDefaultId);
        });
       
        $newSubscription = Subscription::findOrFail($newDefaultId);
        LawFirm::whereHas('currentSubscription', fn ($q) =>
            $q->where('is_default_assigned', true)
        )->each(fn ($firm) =>
            $this->changeFirmSubscription($firm, $newSubscription,true)
        );
    }

    /**
     * Assign default subscription to a new firm
     *
     * @param LawFirm $firm
     * @return FirmSubscription
     */
    public function assignDefault(LawFirm $firm): FirmSubscription
    {
        $defaultId = AppSetting::getSetting('default_subscription_id');
        $subscription = Subscription::findOrFail($defaultId);

        $snapshot = FirmSubscription::createFromTemplate($firm, $subscription, true);
        // Update firm to use this snapshot
        $firm->update(['current_subscription_id' => $snapshot->id]);

        return $snapshot;
    }

    /**
     * Upgrade/Downgrade a firm's subscription
     *
     * @param LawFirm $firm
     * @param Subscription $subscription
     * @return FirmSubscription
     */
    public function changeFirmSubscription(LawFirm $firm, Subscription $subscription, bool $isDefaultAssigned = false): FirmSubscription
    {
        $snapshot = FirmSubscription::createFromTemplate($firm, $subscription, $isDefaultAssigned);
        $firm->update(['current_subscription_id' => $snapshot->id]);
        return $snapshot;
    }

    /**
     * Check if a firm can create a user of a given role
     *
     * @param LawFirm $firm
     * @param string $role ('ADMIN'|'LAWYER'|'CLIENT')
     * @return bool
     */
    public function canCreateUser(LawFirm $firm, string $role): bool
    {
        $subscription = $firm->currentSubscription;

        if (!$subscription) {
            return false;
        }

        $count = $firm->users()->where('role', $role)->count();

        return match($role) {
            'ADMIN' => $count < $subscription->max_admins,
            'LAWYER' => $count < $subscription->max_lawyers,
            'CLIENT' => $count < $subscription->max_clients,
            default => false,
        };
    }

    /**
     * Check if a user can upload a new document
     *
     * @param LawFirm $firm
     * @param int $userId
     * @return bool
     */
    public function canUploadDocument(LawFirm $firm, int $userId): bool
    {
        $subscription = $firm->currentSubscription;

        if (!$subscription) {
            return false;
        }
        $count = $firm->documents()->where('owner_id', $userId)->count();
        return $count < $subscription->max_documents_per_user;
    }

    /**
     * Get current subscription snapshot for a firm
     *
     * @param LawFirm $firm
     * @return FirmSubscription|null
     */
    public function getCurrentSubscription(LawFirm $firm): ?FirmSubscription
    {
        return $firm->currentSubscription;
    }

    public function syncFirmSubscription(LawFirm $firm, Subscription $subscription,bool $isDefaultAssigned = false): FirmSubscription
    {
        $current = $firm->currentSubscription;

        if (!$current) {
            return $this->changeFirmSubscription($firm, $subscription,$isDefaultAssigned);
        }

        // Compare relevant fields
        $fields = ['max_admins', 'max_lawyers', 'max_clients', 'max_documents_per_user', 'name'];
        $needsUpdate = false;
        
        foreach ($fields as $field) {
            if ($current->$field != $subscription->$field) {
                $needsUpdate = true;
                break;
            }
        }

        if ($needsUpdate) {
            // Create a new snapshot but keep subscription_id reference updated
            return $this->changeFirmSubscription($firm, $subscription,$isDefaultAssigned);
        }

        // No change needed, return existing snapshot
        return $current;
    }
}