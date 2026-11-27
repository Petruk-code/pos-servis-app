<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $cost = fake()->numberBetween(10000, 200000);
        $selling = $cost + fake()->numberBetween(5000, 100000);

        return [
            'category_id' => Category::factory(),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'name' => fake()->words(3, true),
            'cost_price' => $cost,
            'selling_price' => $selling,
            'stock' => fake()->numberBetween(5, 50),
            'minimum_stock' => 2,
            'unit' => 'pcs',
            'is_active' => true,
        ];
    }
}
