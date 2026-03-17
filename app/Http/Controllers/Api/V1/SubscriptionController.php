<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ApiResponse;
use App\Models\Subscription;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubscriptionController extends BaseApiController
{
    /**
     * Display a listing of all subscription plans (Platform Admin only)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            // Only platform admin can view subscription plans
            $authError = $this->authorizePlatformAdmin();
            if ($authError) {
                return $authError;
            }

            $subscriptions = Subscription::with('lawFirms')->paginate(15);

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
    public function store(Request $request)
    {
        try {
            // Only platform admin can create subscription plans
            $authError = $this->authorizePlatformAdmin();
            if ($authError) {
                return $authError;
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:subscriptions',
                'max_admins' => 'required|integer|min:1|max:100',
                'max_lawyers' => 'required|integer|min:1|max:1000',
                'max_clients' => 'required|integer|min:1|max:10000',
                'max_documents_per_user' => 'required|integer|min:1|max:10000',
            ]);

            $subscription = Subscription::create($validated);

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
            // Only platform admin can view subscription details
            $authError = $this->authorizePlatformAdmin();
            if ($authError) {
                return $authError;
            }

            $subscription->load('lawFirms');
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
    public function update(Request $request, Subscription $subscription)
    {
        try {
            // Only platform admin can update subscription plans
            $authError = $this->authorizePlatformAdmin();
            if ($authError) {
                return $authError;
            }

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255|unique:subscriptions,name,' . $subscription->id,
                'max_admins' => 'sometimes|required|integer|min:1|max:100',
                'max_lawyers' => 'sometimes|required|integer|min:1|max:1000',
                'max_clients' => 'sometimes|required|integer|min:1|max:10000',
                'max_documents_per_user' => 'sometimes|required|integer|min:1|max:10000',
            ]);

            $subscription->update($validated);
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
            // Only platform admin can delete subscription plans
            $authError = $this->authorizePlatformAdmin();
            if ($authError) {
                return $authError;
            }

            // Check if subscription is being used by any firms
            if ($subscription->lawFirms()->count() > 0) {
                return ApiResponse::error(
                    'Cannot delete subscription plan that is currently assigned to law firms',
                    null,
                    409
                );
            }

            $subscription->delete();

            return ApiResponse::success(null, 'Subscription plan deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Set a subscription as the default for new firms (Platform Admin only)
     *
     * @param Subscription $subscription
     * @return \Illuminate\Http\JsonResponse
     */
    public function setAsDefault(Subscription $subscription)
    {
        try {
            // Only platform admin can set default subscription
            $authError = $this->authorizePlatformAdmin();
            if ($authError) {
                return $authError;
            }

            // Store in database settings
            AppSetting::setSetting('default_subscription_id', $subscription->id);

            return ApiResponse::success([
                'subscription' => $subscription,
                'default_subscription_id' => $subscription->id
            ], 'Default subscription updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get the current default subscription (Platform Admin only)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDefault()
    {
        try {
            // Only platform admin can view default subscription
            $authError = $this->authorizePlatformAdmin();
            if ($authError) {
                return $authError;
            }

            $defaultId = AppSetting::getSetting('default_subscription_id') ?? config('app.default_subscription_id') ?? Subscription::first()?->id;
            $defaultSubscription = Subscription::find($defaultId);

            if (!$defaultSubscription) {
                return ApiResponse::notFound('Default subscription not found');
            }

            return ApiResponse::success([
                'subscription' => $defaultSubscription,
                'default_subscription_id' => $defaultId
            ], 'Default subscription retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}