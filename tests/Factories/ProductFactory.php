<?php

namespace audunru\ModelHistory\Tests\Factories;

use audunru\ModelHistory\Tests\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'description'                => $this->faker->sentence,
            'purchased_at'               => $this->faker->dateTimeThisYear->setTime(0, 0),
            'gross_cost'                 => $this->faker->numberBetween(100, 1000),
            'tax_rate'                   => $this->faker->numberBetween(0, 25),
            'seller_name'                => $this->faker->name,
            'seller_address'             => $this->faker->address,
            'seller_phone'               => $this->faker->phoneNumber,
            'seller_identification'      => $this->faker->word,
        ];
    }
}
