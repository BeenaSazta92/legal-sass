<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function register(array $data): array
    {
        
        if (User::where('role', 'SYSTEM_ADMIN')->lockForUpdate()->exists()) { 
            throw new \Exception('A SYSTEM_ADMIN already exists.');
        }

        $user = User::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'role' => 'SYSTEM_ADMIN',
            'firm_id' => null,
        ]);

        return [
            'user' => $user,
            'token' => $user->generateToken(),
        ];
    }

    public function login(array $credentials): ?array
    {
        if (!Auth::attempt($credentials)) {
            return null;
        }

        $user = Auth::user();

        return [
            'user' => $user,
            'token' => $user->generateToken(),
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}