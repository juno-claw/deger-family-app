<?php

namespace Database\Factories;

use App\Models\GoogleCalendarConnection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoogleCalendarConnection>
 */
class GoogleCalendarConnectionFactory extends Factory
{
    protected $model = GoogleCalendarConnection::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'connection_type' => 'service_account',
            'calendar_id' => fake()->safeEmail(),
            'enabled' => true,
        ];
    }

    /**
     * Create an OAuth2 connection.
     */
    public function oauth2(): static
    {
        return $this->state(fn (array $attributes) => [
            'connection_type' => 'oauth2',
            'access_token' => fake()->sha256(),
            'refresh_token' => fake()->sha256(),
            'token_expires_at' => now()->addHour(),
        ]);
    }

    /**
     * Create a service account connection.
     */
    public function serviceAccount(): static
    {
        return $this->state(fn (array $attributes) => [
            'connection_type' => 'service_account',
            'access_token' => null,
            'refresh_token' => null,
            'token_expires_at' => null,
        ]);
    }

    /**
     * Create a disabled connection.
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => false,
        ]);
    }
}
