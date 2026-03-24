<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\{SubscriptionRequest,UpdateSubscriptionRequest};
use App\Services\SubscriptionService;
use App\Models\AppSetting;

class SubscriptionController extends BaseApiController
{
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }
    /**
     * Display a listing of all subscription plans (Platform Admin only)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $authError = $this->authorizePlatformAdmin();
            if ($authError) return $authError;
            $subscriptions = Subscription::paginate(15);
            return ApiResponse::success($subscriptions, 'Subscription plans retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Store a newly created subscription plan (Platform Admin only)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(SubscriptionRequest $request)
    {
        try {
            $authError = $this->authorizePlatformAdmin();
            if ($authError) return $authError;
            $validated = $request->validated();
            $subscription = Subscription::create($validated);
            if ($request->boolean('is_default')) {
                $this->subscriptionService->changeDefault($subscription->id);
            }
            return ApiResponse::success($subscription, 'Subscription plan created successfully', 201);
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Display the specified subscription plan (Platform Admin only)
     *
     * @param Subscription $subscription
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Subscription $subscription)
    {
        try {
            $authError = $this->authorizePlatformAdmin();
            if ($authError) return $authError;
            //$subscription->load('firmSubscriptions.firm'); // uncomment if required firm also
            return ApiResponse::success($subscription, 'Subscription plan retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update the specified subscription plan (Platform Admin only)
     *
     * @param Request $request
     * @param Subscription $subscription
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateSubscriptionRequest $request, Subscription $subscription)
    {
        try {
            // Only platform admin can update subscription plans
            $authError = $this->authorizePlatformAdmin();
            if ($authError) return $authError;

            $validated = $request->validated();
            $subscription->update($validated);
            if ($request->boolean('is_default')) {
                $this->subscriptionService->changeDefault($subscription->id);
            }
            $subscription->refresh(); // Reload the model with updated data
            return ApiResponse::success($subscription, 'Subscription plan updated successfully');
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->errors(), 'Validation failed');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Remove the specified subscription plan (Platform Admin only)
     *
     * @param Subscription $subscription
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Subscription $subscription)
    {
        try {
            $authError = $this->authorizePlatformAdmin();
            if ($authError) return $authError;
            if ($subscription->firmSubscriptions()->count() > 0) {
                return ApiResponse::error(
                    'Cannot delete subscription plan that is currently assigned to law firms',
                    null,
                    409
                );
            }
            $defaultId = AppSetting::getSetting('default_subscription_id');
            if ($defaultId && $defaultId == $subscription->id) {
                AppSetting::setSetting('default_subscription_id', null);
            }
            $subscription->delete();
            return ApiResponse::success(null, 'Subscription plan deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

     /**
     * Display a listing of all subscription plans (Platform Admin only)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDefaultSubscription()
    {
        try {
            $authError = $this->authorizePlatformAdmin();
            if ($authError) return $authError;
            $defaultId = AppSetting::getSetting('default_subscription_id');
            if (!$defaultId) {
                return ApiResponse::error('Default subscription not configured', null, 404);
            }
            $subscription = Subscription::find($defaultId);
            if (!$subscription) {
                return ApiResponse::error('Default subscription record not found', null, 404);
            }
            return ApiResponse::success(
                $subscription,
                'Default subscription retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}