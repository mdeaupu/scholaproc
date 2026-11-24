<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'company_name' => $this->faker->company,
            'pic_name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'npwp' => $this->faker->unique()->numerify('###########'),
            'nib' => $this->faker->unique()->numerify('##############'),
            'director_name' => $this->faker->name,
            'director_nik' => $this->faker->unique()->numerify('################'),
            'director_npwp' => $this->faker->unique()->numerify('###########'),
            'director_phone' => $this->faker->phoneNumber,
            'director_address' => $this->faker->address,
        ];
    }
}
