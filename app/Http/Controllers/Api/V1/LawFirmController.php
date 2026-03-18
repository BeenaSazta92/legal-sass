<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Models\LawFirm;
use App\Models\Subscription;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LawFirmController extends BaseApiController
{
    /**
     * Display a listing of all law firms (Platform Admin only)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            // Only platform admin can view all firms
            $authError = $this->authorizePlatformAdmin();
            if ($authError) {
                return $authError;
            }

            $firms = LawFirm::with('subscription', 'users')->paginate(15);
            return ApiResponse::success($firms, 'Law firms retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Store a newly created law firm (Platform Admin only)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Only platform admin can create firms
            $authError = $this->authorizePlatformAdmin();
            if ($authError) {
                return $authError;
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:law_firms,name',
                'subscription_id' => 'nullable|exists:subscriptions,id',
            ]);

            $subscriptionId = $validated['subscription_id'] ?? $this->getDefaultSubscriptionId();

            $firm = LawFirm::create([
                'name' => $validated['name'],
                'subscription_id' => $subscriptionId,
                'status' => 'active',
            ]);
            return ApiResponse::success($firm, 'Law firm created successfully', 201);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Display the specified law firm (Platform Admin only)
     * 
     * @param LawFirm $firm
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(LawFirm $firm)
    {
        try {
            // Only platform admin can view firm details
            $authError = $this->authorizePlatformAdmin();
            if ($authError) {
                return $authError;
            }

            $firm->load('subscription', 'users', 'documents');
            return ApiResponse::success($firm, 'Law firm retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update the specified law firm (Platform Admin only)
     * 
     * @param Request $request
     * @param LawFirm $firm
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, LawFirm $firm)
    {
        try {
            // Only platform admin can update firms
            $authError = $this->authorizePlatformAdmin();
            if ($authError) {
                return $authError;
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:law_firms,name,' . $firm->id,
                'subscription_id' => 'sometimes|required|exists:subscriptions,id',
                'status' => 'sometimes|required|in:active,suspended',
            ]);

            $firm->update($validated);
            $firm->refresh(); // Reload the model with updated data

            return ApiResponse::success($firm, 'Law firm updated successfully');
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Remove the specified law firm (Platform Admin only) - Soft Delete
     * 
     * @param LawFirm $firm
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(LawFirm $firm)
    {
        try {
            // Only platform admin can delete firms
            $authError = $this->authorizePlatformAdmin();
            if ($authError) {
                return $authError;
            }

            $firm->delete(); // Soft delete

            return ApiResponse::success(null, 'Law firm deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get default subscription ID from database settings
     * 
     * @return int
     */
    private function getDefaultSubscriptionId()
    {
        // Try to get from database settings first, then fall back to config
        $defaultId = AppSetting::getSetting('default_subscription_id');
        
        if (!$defaultId) {
            $defaultId = config('app.default_subscription_id') ?? Subscription::first()?->id ?? 1;
        }
        
        return (int) $defaultId;
    }

    /**
     * Get trashed (soft deleted) law firms (Platform Admin only)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function trashed()
    {
        try {
            // Only platform admin can view trashed firms
            $authError = $this->authorizePlatformAdmin();
            if ($authError) {
                return $authError;
            }

            $trashedFirms = LawFirm::onlyTrashed()->with('subscription')->paginate(15);

            return ApiResponse::success($trashedFirms, 'Trashed law firms retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Restore a soft deleted law firm (Platform Admin only)
     * 
     * @param int $firmId
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($firmId)
    {
        try {
            // Only platform admin can restore firms
            $authError = $this->authorizePlatformAdmin();
            if ($authError) {
                return $authError;
            }

            $firm = LawFirm::withTrashed()->findOrFail($firmId);
            $firm->restore();

            return ApiResponse::success($firm, 'Law firm restored successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Permanently delete a law firm (Platform Admin only)
     * 
     * @param int $firmId
     * @return \Illuminate\Http\JsonResponse
     */
    public function forceDelete($firmId)
    {
        try {
            // Only platform admin can force delete firms
            $authError = $this->authorizePlatformAdmin();
            if ($authError) {
                return $authError;
            }

            $firm = LawFirm::withTrashed()->findOrFail($firmId);
            $firm->forceDelete();

            return ApiResponse::success(null, 'Law firm permanently deleted');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
