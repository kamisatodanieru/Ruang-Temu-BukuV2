<?php

namespace Database\Factories;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
            'role_id' => fn () => Role::query()->firstOrCreate(['name' => RoleName::Anggota->value])->id,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('PasswordAman12345!'),
            'status' => UserStatus::Active,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::query()->firstOrCreate(['name' => RoleName::Admin->value])->id,
        ]);
    }

    public function petugas(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::query()->firstOrCreate(['name' => RoleName::Petugas->value])->id,
        ]);
    }

    public function anggota(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::query()->firstOrCreate(['name' => RoleName::Anggota->value])->id,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Inactive,
        ]);
    }
}
