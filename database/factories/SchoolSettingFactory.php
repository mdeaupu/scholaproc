<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\SchoolSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolSetting>
 */
class SchoolSettingFactory extends Factory
{
    protected $model = SchoolSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => School::factory(),
            'kop_pusat' => 'Pemerintah Provinsi ' . $this->faker->state(),
            'kop_provinsi' => 'Dinas Pendidikan Wilayah ' . $this->faker->randomDigitNotNull(),
            'kop_sub_wilayah' => 'Cabang Dinas ' . $this->faker->city(),
        ];
    }
}
