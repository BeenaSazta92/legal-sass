<?php

namespace App\Http\Controllers;

use App\Models\LawFirm;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LawFirmController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorizeSystemAdmin();

        $firms = LawFirm::with('subscription')->get();

        return response()->json($firms);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorizeSystemAdmin();

        $request->validate([
            'name' => 'required|string|max:255',
            'subscription_id' => 'nullable|exists:subscriptions,id',
        ]);

        $subscriptionId = $request->subscription_id ?? $this->getDefaultSubscriptionId();

        $firm = LawFirm::create([
            'name' => $request->name,
            'subscription_id' => $subscriptionId,
            'status' => 'active',
        ]);

        return response()->json($firm, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(LawFirm $firm)
    {
        $this->authorizeSystemAdmin();

        return response()->json($firm->load('subscription'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LawFirm $firm)
    {
        $this->authorizeSystemAdmin();

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'subscription_id' => 'sometimes|required|exists:subscriptions,id',
            'status' => 'sometimes|required|in:active,suspended',
        ]);

        $firm->update($request->only(['name', 'subscription_id', 'status']));

        return response()->json($firm);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LawFirm $firm)
    {
        $this->authorizeSystemAdmin();

        $firm->delete();

        return response()->json(['message' => 'Firm deleted']);
    }

    private function authorizeSystemAdmin()
    {
        if (Auth::user()->role !== 'SYSTEM_ADMIN') {
            abort(403, 'Unauthorized');
        }
    }

    private function getDefaultSubscriptionId()
    {
        // For now, get the first subscription, or set a config
        return Subscription::first()->id ?? 1;
    }
}
