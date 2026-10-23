<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => fake()->randomElement(['superadmin', 'admin', 'school']),
            'school_id' => null,
            'status' => 'active',
        ];
    }

    public function superAdmin(): static
    {
        return $this->role('superadmin');
    }

    public function admin(): static
    {
        return $this->role('admin');
    }

    public function school(): static
    {
        return $this->role('school')->state(fn (array $attributes) => [
            'school_id' => School::factory(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    protected function role(string $role): static
    {
        Role::findOrCreate($role);

        return $this
            ->state(fn (array $attributes) => ['role' => $role])
            ->afterCreating(fn (User $created) => $created->assignRole($role));
    }
}
