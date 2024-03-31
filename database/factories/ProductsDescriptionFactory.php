<?php

namespace Database\Factories;

use App\Models\ProductsDescription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ProductsDescriptionFactory extends Factory
{
    protected $model = ProductsDescription::class;

    public function definition(): array
    {
        return [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'language' => $this->faker->word(),
            'description' => $this->faker->text(),
            'tag' => $this->faker->word(),
        ];
    }
}
