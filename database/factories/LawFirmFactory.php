<?php

namespace Database\Factories;

use App\Models\LawFirm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LawFirm>
 */
class LawFirmFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'subscription_id' => 1, // Default to first subscription
            'status' => 'active',
        ];
    }
}
