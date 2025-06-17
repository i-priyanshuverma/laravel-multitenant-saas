<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $name = fake()->word();
        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'stripe_price_id' => 'price_' . Str::random(10),
            'price_monthly' => fake()->randomFloat(2, 10, 100),
            'price_yearly' => fake()->randomFloat(2, 100, 1000),
            'max_users' => fake()->numberBetween(5, 50),
            'max_storage_gb' => fake()->numberBetween(10, 100),
            'features' => ['Feature 1', 'Feature 2'],
            'is_active' => true,
        ];
    }
}
