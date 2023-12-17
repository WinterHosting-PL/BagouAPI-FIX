<?php

namespace Database\Factories;

use App\Models\Mailinglist;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class MailinglistFactory extends Factory
{
    protected $model = Mailinglist::class;

    public function definition(): array
    {
        return [
            'infomaniak_id' => $this->faker->word() ,
            'name' => $this->faker->name() ,
            'created_at' => Carbon::now() ,
            'updated_at' => Carbon::now() ,
        ];
    }
}
