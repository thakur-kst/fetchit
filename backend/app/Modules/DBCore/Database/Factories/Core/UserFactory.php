<?php

namespace Modules\DBCore\Database\Factories\Core;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\DBCore\Models\Core\User;

/**
 * User Factory
 *
 * Creates User models for testing.
 * Users are stored in the 'fetchit' schema.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\DBCore\Models\Core\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'uuid' => (string) Str::uuid(),
            'name' => "{$firstName} {$lastName}",
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'status' => 'active',
            'google_id' => null,
            'picture' => null,
            'locale' => 'en',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a user with Google OAuth
     */
    public function withGoogle(string $googleId = null, string $picture = null): static
    {
        return $this->state(fn (array $attributes) => [
            'google_id' => $googleId ?? Str::uuid()->toString(),
            'picture' => $picture ?? fake()->imageUrl(),
            'password' => null, // Google OAuth users don't have local passwords
        ]);
    }

    /**
     * Create a user without password (for Google OAuth users)
     */
    public function withoutPassword(): static
    {
        return $this->state(fn (array $attributes) => [
            'password' => null,
        ]);
    }

    /**
     * Create an inactive user
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Create a suspended user
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }
}
