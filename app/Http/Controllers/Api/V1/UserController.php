<?php

namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Services\UserService;
use App\Http\Requests\UserRequest;


class UserController extends BaseApiController
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
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
    public function store(UserRequest $request)
    {
        
        try {
            $currentUser = $this->currentUser();
            $newUser = $this->userService->createUser($request->validated(), $currentUser);
            if ($newUser instanceof ApiResponse) return $newUser;
            return ApiResponse::success($newUser, 'User created successfully', 201);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
