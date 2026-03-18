<?php

namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Models\LawFirm;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends BaseApiController
{
    /**
     * List users.
     * - Platform admin sees all users
     * - Firm users see users in their firm
     */
    public function index()
    {
        try {
            // Only platform admins or firm admins/system admins may list users.
            //isPlatformAdmin add this check if isPlatformAdmin should be restricted from creating user
            $currentUser = $this->currentUser();
            if ($currentUser->isLawyer() || $currentUser->isClient()) {
                return ApiResponse::forbidden('You are not allowed to list users');
            }

            if ($this->isPlatformAdmin()) {
                $users = User::with('firm')->paginate(25);
            } else {
                $firmId = $this->getCurrentFirmId();
                $users = User::where('firm_id', $firmId)->with('firm')->paginate(25);
            }
            return ApiResponse::success($users, 'Users retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Create a new user (Admin/Lawyer/Client).
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8',
                'role' => 'required|in:ADMIN,LAWYER,CLIENT',
                'firm_id' => 'required|exists:law_firms,id'
            ]);

            $currentUser = $this->currentUser();
            $firmId = (int) $validated['firm_id'];

            // Lawyers and clients cannot create users at all
            if ($currentUser->isLawyer() || $currentUser->isClient()) {
                return ApiResponse::forbidden('You are not allowed to create users');
            }

            // Determine what the creator is allowed to create
            if ($currentUser->isPlatformAdmin()) {
                // Platform admin can only create firm-level admins (ADMIN)
                if ($validated['role'] !== 'ADMIN') {
                    return ApiResponse::forbidden('Platform admin can only create ADMIN users');
                }
            } else {
                // Non-platform users can only create lawyers/clients for their own firm
                if (! $currentUser->canManageUsersInFirm($firmId)) {
                    return ApiResponse::forbidden('You can only create users inside your own firm');
                }

                if (! in_array($validated['role'], ['LAWYER', 'CLIENT'], true)) {
                    return ApiResponse::forbidden('You are not allowed to create that role');
                }

                // Ensure the user is creating within their firm
                if ($currentUser->firm_id !== $firmId) {
                    return ApiResponse::forbidden('You can only create users in your own firm');
                }
            }

            $lawFirm = LawFirm::findOrFail($firmId);

            // Enforce subscription limits for the firm
            $limitError = $this->checkSubscriptionLimit($lawFirm, $validated['role']);
            if ($limitError) {
                return $limitError;
            }

            $newUser = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'firm_id' => $firmId,
            ]);

            return ApiResponse::success($newUser, 'User created successfully', 201);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Validate subscription limits for the firm when creating a new user.
     */
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

        if (isset($roleLimits[$role])) {
            $count = $firm->users()->where('role', $role)->count();
            if ($count >= $roleLimits[$role]) {
                return ApiResponse::error("{$role} limit reached for this plan", null, 422);
            }
        }
        return null;
    }
}
