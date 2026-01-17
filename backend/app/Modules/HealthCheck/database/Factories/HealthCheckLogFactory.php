<?php

namespace Modules\HealthCheck\Database\Factories;

use Modules\DBCore\Models\CustomerPortal\HealthCheckLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * HealthCheckLog Factory
 *
 * Generates fake health check log data for testing and seeding.
 *
 * @package HealthCheck
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Modules\DBCore\Models\CustomerPortal\HealthCheckLog>
 */
class HealthCheckLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = HealthCheckLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $endpoints = [
            'api/v1/health',
            'api/v1/health/detailed',
            'api/v1/health/liveness',
            'api/v1/health/readiness',
        ];

        $healthStatuses = ['healthy', 'unhealthy', 'degraded'];
        $healthStatus = $this->faker->randomElement($healthStatuses);

        // Determine status code based on health status
        $statusCode = match ($healthStatus) {
            'healthy' => 200,
            'degraded' => $this->faker->randomElement([200, 503]),
            'unhealthy' => $this->faker->randomElement([503, 500]),
            default => 200,
        };

        $statusTexts = [
            200 => 'OK',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
        ];

        return [
            'endpoint' => $this->faker->randomElement($endpoints),
            'method' => 'GET',
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'status_code' => $statusCode,
            'status_text' => $statusTexts[$statusCode] ?? 'Unknown',
            'health_status' => $healthStatus,
            'execution_time_ms' => $this->faker->randomFloat(2, 0.5, 500),
            'memory_usage_mb' => $this->faker->randomFloat(2, 10, 100),
            'checks_details' => $this->generateChecksDetails($healthStatus),
            'error_message' => $healthStatus === 'unhealthy'
                ? $this->faker->sentence()
                : null,
            'environment' => config('app.env', 'production'),
            'checked_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    /**
     * Generate realistic checks details based on health status
     */
    private function generateChecksDetails(string $healthStatus): array
    {
        $baseChecks = [
            'application' => [
                'status' => 'healthy',
                'message' => 'Application is running',
                'details' => [
                    'php_version' => PHP_VERSION,
                    'laravel_version' => app()->version(),
                ],
            ],
        ];

        if ($healthStatus === 'unhealthy') {
            $baseChecks['database'] = [
                'status' => 'unhealthy',
                'message' => 'Database connection failed',
                'error' => 'Connection timeout',
            ];
            $baseChecks['redis'] = [
                'status' => 'unhealthy',
                'message' => 'Redis connection failed',
                'error' => 'Connection refused',
            ];
        } else {
            $baseChecks['database'] = [
                'status' => 'healthy',
                'message' => 'Database connection successful',
            ];
            $baseChecks['cache'] = [
                'status' => 'healthy',
                'message' => 'Cache is operational',
            ];
        }

        return $baseChecks;
    }

    /**
     * Indicate that the log represents a successful health check.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_code' => 200,
            'status_text' => 'OK',
            'health_status' => 'healthy',
            'error_message' => null,
            'checks_details' => [
                'application' => ['status' => 'healthy'],
                'database' => ['status' => 'healthy'],
                'cache' => ['status' => 'healthy'],
                'redis' => ['status' => 'healthy'],
            ],
        ]);
    }

    /**
     * Indicate that the log represents a failed health check.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_code' => $this->faker->randomElement([500, 503]),
            'status_text' => 'Service Unavailable',
            'health_status' => 'unhealthy',
            'error_message' => $this->faker->sentence(),
            'checks_details' => [
                'application' => ['status' => 'healthy'],
                'database' => [
                    'status' => 'unhealthy',
                    'error' => 'Connection failed',
                ],
            ],
        ]);
    }

    /**
     * Indicate that the log represents a slow health check.
     */
    public function slow(): static
    {
        return $this->state(fn (array $attributes) => [
            'execution_time_ms' => $this->faker->randomFloat(2, 1000, 5000),
        ]);
    }

    /**
     * Set a specific endpoint.
     */
    public function forEndpoint(string $endpoint): static
    {
        return $this->state(fn (array $attributes) => [
            'endpoint' => $endpoint,
        ]);
    }
}
