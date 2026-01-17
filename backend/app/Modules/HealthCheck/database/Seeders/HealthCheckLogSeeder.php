<?php

namespace Modules\HealthCheck\Database\Seeders;

use Modules\DBCore\Models\CustomerPortal\HealthCheckLog;
use Illuminate\Database\Seeder;

/**
 * HealthCheckLog Seeder
 *
 * Seeds the database with sample health check logs for testing and development.
 * Creates a realistic mix of successful, failed, and slow health checks.
 *
 * @package HealthCheck
 */
class HealthCheckLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding health check logs...');

        // Create mostly successful health checks (80%)
        HealthCheckLog::factory()
            ->count(80)
            ->successful()
            ->create();

        $this->command->info('Created 80 successful health check logs');

        // Create some failed health checks (15%)
        HealthCheckLog::factory()
            ->count(15)
            ->failed()
            ->create();

        $this->command->info('Created 15 failed health check logs');

        // Create some slow health checks (5%)
        HealthCheckLog::factory()
            ->count(5)
            ->slow()
            ->create();

        $this->command->info('Created 5 slow health check logs');

        // Create specific endpoint logs
        $endpoints = [
            'api/v1/health',
            'api/v1/health/detailed',
            'api/v1/health/liveness',
            'api/v1/health/readiness',
        ];

        foreach ($endpoints as $endpoint) {
            HealthCheckLog::factory()
                ->count(10)
                ->forEndpoint($endpoint)
                ->create();
        }

        $this->command->info('Created 10 logs for each endpoint (40 total)');

        $totalCount = HealthCheckLog::count();
        $this->command->info("✅ Total health check logs created: {$totalCount}");

        // Display some statistics
        $this->displayStatistics();
    }

    /**
     * Display seeding statistics
     */
    private function displayStatistics(): void
    {
        $this->command->newLine();
        $this->command->info('📊 Health Check Log Statistics:');
        $this->command->table(
            ['Metric', 'Value'],
            [
                ['Total Logs', HealthCheckLog::count()],
                ['Successful', HealthCheckLog::where('health_status', 'healthy')->count()],
                ['Failed', HealthCheckLog::failed()->count()],
                ['Slow (>1000ms)', HealthCheckLog::slow()->count()],
                ['Avg Execution Time', number_format(HealthCheckLog::avg('execution_time_ms'), 2) . ' ms'],
            ]
        );
    }
}
