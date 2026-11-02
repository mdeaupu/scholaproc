<?php

namespace Database\Factories;

use App\Models\Subsidiary;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subsidiary>
 */
class SubsidiaryFactory extends Factory
{
    protected $model = Subsidiary::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'npwp' => $this->faker->unique()->numerify('###########'),
            'director_name' => $this->faker->name,
            'pic_name' => $this->faker->name,
            'email' => $this->faker->companyEmail,
            'phone' => $this->faker->phoneNumber,
            'address' => $this->faker->address,
            'nib' => $this->faker->unique()->numerify('##############'),
            'director_nik' => $this->faker->unique()->numerify('################'),
            'status' => 'active',
        ];
    }
}
