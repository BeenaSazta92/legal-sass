<?php

namespace App\Services;

use App\Models\LawFirm;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;


class UserService
{
    public function createUser(array $data, User $currentUser)
    {
        $firmId = $currentUser->isPlatformAdmin() ? (int) $data['firm_id'] : $currentUser->firm_id;
        // Permission check
        if ($currentUser->isLawyer() || $currentUser->isClient()) {
            throw new AuthorizationException('You are not allowed to create users');
        }

        if ($currentUser->isPlatformAdmin() && $data['role'] !== 'ADMIN') {
            throw new AuthorizationException('Platform admin can only create ADMIN users');
        }

        if (! $currentUser->isPlatformAdmin()) {
            if (! $currentUser->canManageUsersInFirm($firmId)) {
                throw new AuthorizationException('You can only create users inside your own firm');
            }

            if (! in_array($data['role'], ['LAWYER', 'CLIENT'], true)) {
                throw new AuthorizationException('You are not allowed to create that role');
            }

            if ($currentUser->firm_id !== $firmId) {
                throw new AuthorizationException('You can only create users in your own firm');
            }
        }

        // Check subscription limits
        $firm = LawFirm::findOrFail($firmId);
        $limitError = $this->checkSubscriptionLimit($firm, $data['role']);
        if ($limitError) return $limitError;

        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'firm_id' => $firmId,
        ]);
    }

    private function checkSubscriptionLimit(LawFirm $firm, string $role)
    {
        $currentSubscription = $firm->currentSubscription;//$firm->subscription;
        if (! $currentSubscription) {
            return ApiResponse::error('Firm has no subscription plan assigned', null, 422);
        }

        $roleLimits = [
            'ADMIN' => $currentSubscription->max_admins,
            'LAWYER' => $currentSubscription->max_lawyers,
            'CLIENT' => $currentSubscription->max_clients,
        ];

        $count = $firm->users()->where('role', $role)->count();
        if ($count >= $roleLimits[$role]) {
            throw new AuthorizationException("{$role} limit reached for this plan");
        }
        return null;
    }
}
?>