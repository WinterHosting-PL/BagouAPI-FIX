<?php

namespace Database\Factories;

use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class DiscountFactory extends Factory
{
    protected $model = Discount::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->word() ,
            'value' => $this->faker->word() ,
            'created_at' => Carbon::now() ,
            'updated_at' => Carbon::now() ,
        ];
    }
}
