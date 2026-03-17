<?php

namespace Database\Seeders;

use App\Models\Subscription;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subscriptions = [
            [
                'name' => 'Free',
                'max_admins' => 1,
                'max_lawyers' => 2,
                'max_clients' => 10,
                'max_documents_per_user' => 20,
            ],
            [
                'name' => 'Starter',
                'max_admins' => 2,
                'max_lawyers' => 5,
                'max_clients' => 50,
                'max_documents_per_user' => 100,
            ],
            [
                'name' => 'Pro',
                'max_admins' => 5,
                'max_lawyers' => 20,
                'max_clients' => 200,
                'max_documents_per_user' => 500,
            ],
        ];

        foreach ($subscriptions as $subscription) {
            Subscription::create($subscription);
        }

        // Set the Free plan (ID 1) as the default subscription
        config(['app.default_subscription_id' => 1]);
    }
}
