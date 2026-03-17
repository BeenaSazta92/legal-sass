<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Auth;

class BaseApiController extends Controller
{
    /**
     * Current authenticated user
     */
    protected function currentUser()
    {
        return Auth::user();
    }

    /**
     * Get current user's firm ID
     */
    protected function getCurrentFirmId()
    {
        return Auth::user()?->firm_id;
    }

    /**
     * Check if current user is system admin
     * (Platform admin OR Firm system admin)
     */
    protected function isSystemAdmin(): bool
    {
        return Auth::user()?->isSystemAdmin() ?? false;
    }

    /**
     * Check if current user is platform admin only
     */
    protected function isPlatformAdmin(): bool
    {
        return Auth::user()?->isPlatformAdmin() ?? false;
    }

    /**
     * Check if current user is firm system admin
     */
    protected function isFirmSystemAdmin(): bool
    {
        return Auth::user()?->isFirmSystemAdmin() ?? false;
    }

    /**
     * Check if current user is firm admin
     */
    protected function isFirmAdmin(): bool
    {
        return Auth::user()?->isFirmAdmin() ?? false;
    }

    /**
     * Authorize: Platform Admin only
     * Returns error response if not authorized
     */
    protected function authorizePlatformAdmin()
    {
        if (!Auth::user()?->isPlatformAdmin()) {
            return ApiResponse::forbidden('This action requires platform admin access');
        }
        return null;
    }

    /**
     * Authorize: Only System Admin (Platform or Firm) can access
     * Returns error response if not authorized
     */
    protected function authorizeSystemAdmin()
    {
        if (!$this->isSystemAdmin()) {
            return ApiResponse::forbidden('This action requires system admin access');
        }
        return null;
    }

    /**
     * Authorize: Firm-level admin (System Admin or Admin) of specified firm
     * Returns error response if not authorized
     */
    protected function authorizeFirmAdmin($requiredFirmId = null)
    {
        $user = $this->currentUser();

        // Platform admin can access any firm
        if ($user->isPlatformAdmin()) {
            return null;
        }

        // Firm system admin or firm admin can manage their own firm
        if (($user->isFirmSystemAdmin() || $user->isFirmAdmin()) && 
            $user->belongsToFirm($requiredFirmId)) {
            return null;
        }

        return ApiResponse::forbidden('You can only access your own firm');
    }

    /**
     * Authorize: Check if user can access firm resources
     * Returns error response if not authorized
     */
    protected function authorizeFirmAccess($firmId)
    {
        if (!$this->currentUser()->canAccessDocument($firmId)) {
            return ApiResponse::forbidden('You do not have access to this firm\'s resources');
        }
        return null;
    }

    /**
     * Get all firms (System Admin only) or user's firm (others)
     */
    protected function getAccessibleFirms()
    {
        if ($this->isSystemAdmin()) {
            // System admin can see all firms
            return null; // Signal to fetch all
        }

        // Firm users only see their firm
        return $this->getCurrentFirmId();
    }

    /**
     * Helper to build error response and return
     */
    protected function handleException(\Exception $e)
    {
        if (strpos($e->getMessage(), 'Unauthorized') !== false || 
            strpos($e->getMessage(), 'admin') !== false) {
            return ApiResponse::forbidden($e->getMessage());
        }

        return ApiResponse::error($e->getMessage(), null, 500);
    }
}
