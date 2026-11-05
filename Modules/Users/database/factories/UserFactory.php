<?php

namespace Modules\Users\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Users\Models\User;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '+20'.$this->faker->unique()->numerify('1#########'),
            'password' => 'password', // hashed by model cast
            'is_active' => true,
        ];
    }
}
