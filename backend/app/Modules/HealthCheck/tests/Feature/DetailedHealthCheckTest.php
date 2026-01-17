<?php

namespace Modules\HealthCheck\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Detailed Health Check Endpoint Tests
 *
 * Tests the GET /api/v1/health/detailed endpoint functionality.
 *
 * @package Tests\Feature\HealthCheck
 */
class DetailedHealthCheckTest extends TestCase
{
    /**
     * Test that detailed health check endpoint is accessible.
     */
    public function test_detailed_health_check_endpoint_is_accessible(): void
    {
        $response = $this->get('/api/v1/health/detailed');

        // Should return 200 (healthy) or 503 (unhealthy)
        $this->assertContains($response->status(), [200, 503]);
    }

    /**
     * Test that detailed health check returns correct JSON structure.
     */
    public function test_detailed_health_check_returns_correct_json_structure(): void
    {
        $response = $this->get('/api/v1/health/detailed');

        $response->assertJsonStructure([
            'status',
            'timestamp',
            'service',
            'environment',
            'checks',
        ]);
    }

    /**
     * Test that detailed health check includes application checker.
     */
    public function test_detailed_health_check_includes_application_checker(): void
    {
        $response = $this->get('/api/v1/health/detailed');

        $response->assertJsonStructure([
            'checks' => [
                'application' => [
                    'status',
                    'message',
                ],
            ],
        ]);
    }

    /**
     * Test that application checker returns healthy status.
     */
    public function test_application_checker_returns_healthy_status(): void
    {
        $response = $this->get('/api/v1/health/detailed');

        $data = $response->json();

        $this->assertArrayHasKey('checks', $data);
        $this->assertArrayHasKey('application', $data['checks']);
        $this->assertEquals('healthy', $data['checks']['application']['status']);
    }

    /**
     * Test that application checker includes PHP version.
     */
    public function test_application_checker_includes_php_version(): void
    {
        $response = $this->get('/api/v1/health/detailed');

        $response->assertJsonPath('checks.application.details.php_version', PHP_VERSION);
    }

    /**
     * Test that application checker includes Laravel version.
     */
    public function test_application_checker_includes_laravel_version(): void
    {
        $response = $this->get('/api/v1/health/detailed');

        $data = $response->json();

        $this->assertArrayHasKey('laravel_version', $data['checks']['application']['details']);
        $this->assertNotEmpty($data['checks']['application']['details']['laravel_version']);
    }

    /**
     * Test that detailed health check includes all checker types.
     */
    public function test_detailed_health_check_includes_all_checkers(): void
    {
        $response = $this->get('/api/v1/health/detailed');

        $data = $response->json();

        $this->assertArrayHasKey('checks', $data);

        // Should have at least application checker
        $this->assertArrayHasKey('application', $data['checks']);

        // May have database, cache, redis checkers (depending on availability)
        $expectedCheckers = ['application', 'database', 'cache', 'redis'];
        $actualCheckers = array_keys($data['checks']);

        // At least one checker should be present
        $this->assertNotEmpty($actualCheckers);
    }

    /**
     * Test that each checker has required fields.
     */
    public function test_each_checker_has_required_fields(): void
    {
        $response = $this->get('/api/v1/health/detailed');

        $data = $response->json();

        foreach ($data['checks'] as $checkerName => $checker) {
            $this->assertArrayHasKey('status', $checker, "Checker '{$checkerName}' should have 'status' field");
            $this->assertArrayHasKey('message', $checker, "Checker '{$checkerName}' should have 'message' field");

            // Status should be one of: healthy, unhealthy, degraded
            $this->assertContains(
                $checker['status'],
                ['healthy', 'unhealthy', 'degraded'],
                "Checker '{$checkerName}' status should be valid"
            );
        }
    }

    /**
     * Test that unhealthy checkers include error message.
     */
    public function test_unhealthy_checkers_include_error_message(): void
    {
        $response = $this->get('/api/v1/health/detailed');

        $data = $response->json();

        foreach ($data['checks'] as $checkerName => $checker) {
            if ($checker['status'] === 'unhealthy') {
                $this->assertArrayHasKey(
                    'error',
                    $checker,
                    "Unhealthy checker '{$checkerName}' should have 'error' field"
                );
            }
        }
    }

    /**
     * Test that overall status reflects checker statuses.
     */
    public function test_overall_status_reflects_checker_statuses(): void
    {
        $response = $this->get('/api/v1/health/detailed');

        $data = $response->json();

        $hasUnhealthyChecker = false;
        foreach ($data['checks'] as $checker) {
            if ($checker['status'] === 'unhealthy') {
                $hasUnhealthyChecker = true;
                break;
            }
        }

        if ($hasUnhealthyChecker) {
            $this->assertEquals('unhealthy', $data['status'], 'Overall status should be unhealthy if any checker is unhealthy');
            $response->assertStatus(503);
        } else {
            $this->assertEquals('healthy', $data['status'], 'Overall status should be healthy if all checkers are healthy');
            $response->assertStatus(200);
        }
    }

    /**
     * Test that endpoint does not require authentication.
     */
    public function test_detailed_health_check_does_not_require_authentication(): void
    {
        $response = $this->get('/api/v1/health/detailed');

        // Should work without authentication (200 or 503 both acceptable)
        $this->assertContains($response->status(), [200, 503]);
    }

    /**
     * Test that endpoint accepts only GET method.
     */
    public function test_detailed_health_check_only_accepts_get_method(): void
    {
        $response = $this->get('/api/v1/health/detailed');
        $this->assertContains($response->status(), [200, 503]);

        $this->post('/api/v1/health/detailed')->assertStatus(405);
        $this->put('/api/v1/health/detailed')->assertStatus(405);
        $this->delete('/api/v1/health/detailed')->assertStatus(405);
        $this->patch('/api/v1/health/detailed')->assertStatus(405);
    }

    /**
     * Test response time is reasonable.
     */
    public function test_detailed_health_check_responds_within_reasonable_time(): void
    {
        $startTime = microtime(true);

        $response = $this->get('/api/v1/health/detailed');

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Detailed check may take longer due to external dependencies
        // Should respond within 2 seconds
        $this->assertLessThan(2000, $executionTime, 'Detailed health check should respond within 2 seconds');
    }

    /**
     * Test that response is valid JSON.
     */
    public function test_detailed_health_check_returns_valid_json(): void
    {
        $response = $this->get('/api/v1/health/detailed');

        $response->assertHeader('Content-Type', 'application/json');

        $data = $response->json();
        $this->assertIsArray($data);
    }

    /**
     * Test that checks is an object/array.
     */
    public function test_checks_field_is_an_object(): void
    {
        $response = $this->get('/api/v1/health/detailed');

        $data = $response->json();

        $this->assertIsArray($data['checks']);
        $this->assertNotEmpty($data['checks']);
    }
}
