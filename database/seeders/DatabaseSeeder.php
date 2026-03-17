<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Subscription;
use App\Models\LawFirm;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create default subscriptions
        $this->call(SubscriptionSeeder::class);

        // Create ONE platform-level system admin (firm_id = NULL)
        User::firstOrCreate(
            ['email' => 'admin@legal-saas.com'],
            [
                'name' => 'Platform Admin',
                'password' => Hash::make('password'),
                'role' => 'SYSTEM_ADMIN',
                'firm_id' => null,  // Platform level - no firm assigned
            ]
        );

        // Create sample law firms with different subscriptions
        $subscriptions = Subscription::all();
        
        foreach ($subscriptions as $subscription) {
            $firm = LawFirm::factory()->create([
                'subscription_id' => $subscription->id,
            ]);

            // Create ADMIN user for this firm
            User::factory(1)->create([
                'firm_id' => $firm->id,
                'role' => 'ADMIN',
            ]);

            // Create LAWYER users for this firm
            User::factory(2)->create([
                'firm_id' => $firm->id,
                'role' => 'LAWYER',
            ]);

            // Create CLIENT users for this firm
            User::factory(5)->create([
                'firm_id' => $firm->id,
                'role' => 'CLIENT',
            ]);
        }
    }
}
