<?php

namespace App\Http\Controllers\Api\V1;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\RequestS\{AuthRegisterRequest,AuthLoginRequest};
use App\Services\AuthService;

class AuthController extends BaseApiController
{
    public function __construct(private AuthService $authService) {}
    /**
     * Register a new user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(AuthRegisterRequest $request)
    {
        try {
            $data = $this->authService->register($request->validated());
            return ApiResponse::success($data, 'User registered successfully', 201);
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 500);
        }
    }

    /**
     * Login user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(AuthLoginRequest $request)
    {
        try {
            $data = $this->authService->login($request->validated());

            if (!$data) {
                return ApiResponse::unauthorized('Invalid email or password');
            }
            return ApiResponse::success($data, 'Logged in successfully');
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 500);
        }
    }

    /**
     * Get authenticated user with context
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        try {
           
            $user = $request->user()->load('firm');
            return ApiResponse::success([
                'user' => $user,
                'context' => $user->getContext(),
            ], 'User profile retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 500);
        }
    }

    /**
     * Logout user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return ApiResponse::success(null, 'Logged out successfully');
        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage(), null, 500);
        }
    }
}
