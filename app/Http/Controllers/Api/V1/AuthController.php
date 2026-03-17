<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseApiController
{
    /**
     * Register a new user
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'role' => 'required|in:' . implode(',', \App\Models\User::ROLES),
                'firm_id' => 'required_unless:role,SYSTEM_ADMIN|nullable|exists:law_firms,id',
            ]);

            // System admin cannot be tied to a firm (always NULL)
            if ($validated['role'] === 'SYSTEM_ADMIN') {
                $validated['firm_id'] = null;
            }

            // Prevent more than one SYSTEM_ADMIN from being created
            if ($validated['role'] === 'SYSTEM_ADMIN' && User::where('role', 'SYSTEM_ADMIN')->exists()) {
                return ApiResponse::error('A SYSTEM_ADMIN already exists. You cannot create another.', null, 400);
            }

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'firm_id' => $validated['firm_id'] ?? null,
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
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

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
