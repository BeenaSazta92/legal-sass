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


class AuthController extends BaseApiController
{
    /**
     * Register a new user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(AuthRegisterRequest $request)
    {
        try {
            $validated = $request->validated();
             // Prevent multiple SYSTEM_ADMIN safely
            if (User::where('role', 'SYSTEM_ADMIN')->lockForUpdate()->exists()) {
                throw new \Exception('A SYSTEM_ADMIN already exists.');
            }

            // Prevent more than one SYSTEM_ADMIN from being created
            // if (User::where('role', 'SYSTEM_ADMIN')->exists()) {
            //     return ApiResponse::error('A SYSTEM_ADMIN already exists. You cannot create another.', null, 400);
            // }

            $user = User::create([
                ...$validated,
                'role' => 'SYSTEM_ADMIN',
                'firm_id' => null,
            ]);

            $token = $user->createToken('API Token')->plainTextToken;
            return ApiResponse::success([
                'user' => $user,
                'token' => $token,
            ], 'User registered successfully', 201);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors(), 'Validation failed');
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
            $validated = $request->validated();

            if (!Auth::attempt($validated)) {
                return ApiResponse::unauthorized('Invalid email or password');
            }
            $user = Auth::user();
            $token = $user->createToken('API Token')->plainTextToken;
            return ApiResponse::success([
                'user' => $user,
                'token' => $token,
            ], 'Logged in successfully');
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
