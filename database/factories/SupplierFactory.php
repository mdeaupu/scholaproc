<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => $this->faker->company,
            'pic_name' => $this->faker->name,
            'email' => $this->faker->companyEmail,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'npwp' => $this->faker->unique()->numerify('###########'),
            'nib' => $this->faker->unique()->numerify('##############'),
            'director_name' => $this->faker->name,
            'director_nik' => $this->faker->unique()->numerify('################'),
            'director_npwp' => $this->faker->optional()->numerify('###########'),
            'director_phone' => $this->faker->optional()->phoneNumber,
            'commissioner_name' => $this->faker->optional()->name,
            'commissioner_nik' => $this->faker->optional()->numerify('################'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
