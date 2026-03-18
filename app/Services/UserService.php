<?php

namespace App\Services;

use App\Models\LawFirm;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Responses\ApiResponse;

class UserService
{
    public function createUser(array $data, User $currentUser)
    {
        $firmId = (int)$data['firm_id'];

        // Permission check
        if ($currentUser->isLawyer() || $currentUser->isClient()) {
            return ApiResponse::forbidden('You are not allowed to create users');
        }

        if ($currentUser->isPlatformAdmin() && $data['role'] !== 'ADMIN') {
            return ApiResponse::forbidden('Platform admin can only create ADMIN users');
        }

        if (! $currentUser->isPlatformAdmin()) {
            if (! $currentUser->canManageUsersInFirm($firmId)) {
                return ApiResponse::forbidden('You can only create users inside your own firm');
            }

            if (! in_array($data['role'], ['LAWYER', 'CLIENT'], true)) {
                return ApiResponse::forbidden('You are not allowed to create that role');
            }

            if ($currentUser->firm_id !== $firmId) {
                return ApiResponse::forbidden('You can only create users in your own firm');
            }
        }

        // Check subscription limits
        $firm = LawFirm::findOrFail($firmId);
        $limitError = $this->checkSubscriptionLimit($firm, $data['role']);
        if ($limitError) {
            return $limitError;
        }

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
        $subscription = $firm->subscription;
        if (! $subscription) {
            return ApiResponse::error('Firm has no subscription plan assigned', null, 422);
        }

        $roleLimits = [
            'ADMIN' => $subscription->max_admins,
            'LAWYER' => $subscription->max_lawyers,
            'CLIENT' => $subscription->max_clients,
        ];

        $count = $firm->users()->where('role', $role)->count();
        if ($count >= $roleLimits[$role]) {
            return ApiResponse::error("{$role} limit reached for this plan", null, 422);
        }

        return null;
    }
}
?>