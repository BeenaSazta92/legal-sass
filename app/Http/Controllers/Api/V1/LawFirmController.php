<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Models\LawFirm;
use App\Models\Subscription;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Services\SubscriptionService;
use App\Http\Requests\{LawFirmStoreRequest,UpdateLawFirmRequest};

class LawFirmController extends BaseApiController
{

    protected SubscriptionService $subscriptionService;
    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }
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

            $firms = LawFirm::with('currentSubscription', 'users')->paginate(15);
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
    public function store(LawFirmStoreRequest $request)
    {
        try {
            // Only platform admin can create firms
            $authError = $this->authorizePlatformAdmin();
            if ($authError) return $authError;

            $validated = $request->validated();
            $subscriptionId = $validated['subscription_id'] ?? $this->getDefaultSubscriptionId();
            if (!$subscriptionId || !Subscription::where('id', $subscriptionId)->exists()) {
                return ApiResponse::error(
                    'Cannot create firm: no valid subscription provided',
                    null,
                    422
                );
            }

            $firm = LawFirm::create([
                'name' => $validated['name'],
                'status' => 'active',
            ]);
            $firm->changeSubscription(Subscription::findOrFail($subscriptionId));
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
            if ($authError) return $authError;
            $firm->load('currentSubscription', 'users', 'documents');
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
    public function update(UpdateLawFirmRequest $request, LawFirm $firm)
    {
        try {
            // Only platform admin can update firms
            $authError = $this->authorizePlatformAdmin();
            if ($authError) return $authError;

            $validated = $request->validated();

            //$firm->update($validated);
            $firm->update(collect($validated)->except('subscription_id')->toArray());

            // Handle plan change
            // if (isset($validated['subscription_id'])) {
            //     $plan = Subscription::findOrFail($validated['subscription_id']);
            //     $firm->changeSubscription($plan);
            // }

            if (isset($validated['subscription_id'])) {
                $newPlan = Subscription::findOrFail($validated['subscription_id']);
                $this->subscriptionService->syncFirmSubscription($firm, $newPlan);
            }
            return ApiResponse::success(
                $firm->load('currentSubscription'),
                'Law firm updated successfully'
            );

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

            $trashedFirms = LawFirm::onlyTrashed()->with('currentSubscription')->paginate(15);

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
