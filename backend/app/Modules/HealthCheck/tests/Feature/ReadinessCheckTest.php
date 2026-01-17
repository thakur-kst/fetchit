<?php

namespace Modules\HealthCheck\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Readiness Check Endpoint Tests
 *
 * Tests the GET /api/v1/health/readiness endpoint functionality.
 * This endpoint is typically used by Kubernetes readiness probes.
 *
 * @package Tests\Feature\HealthCheck
 */
class ReadinessCheckTest extends TestCase
{
    /**
     * Test that readiness check endpoint is accessible.
     */
    public function test_readiness_check_endpoint_is_accessible(): void
    {
        $response = $this->get('/api/v1/health/readiness');

        // 200 if ready, 503 if not ready
        $this->assertContains($response->status(), [200, 503]);
    }

    /**
     * Test that readiness check returns correct JSON structure.
     */
    public function test_readiness_check_returns_correct_json_structure(): void
    {
        $response = $this->get('/api/v1/health/readiness');

        $response->assertJsonStructure([
            'ready',
            'timestamp',
            'checks',
        ]);
    }

    /**
     * Test that ready field is boolean.
     */
    public function test_ready_field_is_boolean(): void
    {
        $response = $this->get('/api/v1/health/readiness');

        $data = $response->json();

        $this->assertIsBool($data['ready'], 'ready field should be boolean');
    }

    /**
     * Test that readiness check includes critical dependency checks.
     */
    public function test_readiness_check_includes_critical_dependencies(): void
    {
        $response = $this->get('/api/v1/health/readiness');

        $data = $response->json();

        $this->assertArrayHasKey('checks', $data);
        $this->assertIsArray($data['checks']);

        // Should check database and cache (critical dependencies)
        // At least one dependency should be checked
        $this->assertNotEmpty($data['checks']);
    }

    /**
     * Test that status code matches ready state.
     */
    public function test_status_code_matches_ready_state(): void
    {
        $response = $this->get('/api/v1/health/readiness');

        $data = $response->json();

        if ($data['ready'] === true) {
            $response->assertStatus(200);
        } else {
            $response->assertStatus(503);
        }
    }

    /**
     * Test that timestamp is in ISO 8601 format.
     */
    public function test_readiness_check_timestamp_is_valid_iso8601(): void
    {
        $response = $this->get('/api/v1/health/readiness');

        $data = $response->json();

        $this->assertArrayHasKey('timestamp', $data);

        // Validate ISO 8601 format
        $timestamp = $data['timestamp'];
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $timestamp,
            'Timestamp should be in ISO 8601 format'
        );
    }

    /**
     * Test that each check has status field.
     */
    public function test_each_check_has_status_field(): void
    {
        $response = $this->get('/api/v1/health/readiness');

        $data = $response->json();

        foreach ($data['checks'] as $checkName => $check) {
            $this->assertArrayHasKey(
                'status',
                $check,
                "Check '{$checkName}' should have 'status' field"
            );

            $this->assertContains(
                $check['status'],
                ['healthy', 'unhealthy', 'degraded'],
                "Check '{$checkName}' status should be valid"
            );
        }
    }

    /**
     * Test that unhealthy checks include error information.
     */
    public function test_unhealthy_checks_include_error_information(): void
    {
        $response = $this->get('/api/v1/health/readiness');

        $data = $response->json();

        foreach ($data['checks'] as $checkName => $check) {
            if ($check['status'] === 'unhealthy') {
                $this->assertTrue(
                    isset($check['error']) || isset($check['message']),
                    "Unhealthy check '{$checkName}' should include error or message"
                );
            }
        }
    }

    /**
     * Test that ready is false when any critical dependency is unhealthy.
     */
    public function test_ready_is_false_when_critical_dependency_is_unhealthy(): void
    {
        $response = $this->get('/api/v1/health/readiness');

        $data = $response->json();

        $hasUnhealthyCheck = false;
        foreach ($data['checks'] as $check) {
            if ($check['status'] === 'unhealthy') {
                $hasUnhealthyCheck = true;
                break;
            }
        }

        if ($hasUnhealthyCheck) {
            $this->assertFalse($data['ready'], 'Should not be ready when critical dependency is unhealthy');
            $response->assertStatus(503);
        }
    }

    /**
     * Test that endpoint does not require authentication.
     */
    public function test_readiness_check_does_not_require_authentication(): void
    {
        $response = $this->get('/api/v1/health/readiness');

        // Should work without authentication
        $this->assertContains($response->status(), [200, 503]);
    }

    /**
     * Test that endpoint accepts only GET method.
     */
    public function test_readiness_check_only_accepts_get_method(): void
    {
        $response = $this->get('/api/v1/health/readiness');
        $this->assertContains($response->status(), [200, 503]);

        $this->post('/api/v1/health/readiness')->assertStatus(405);
        $this->put('/api/v1/health/readiness')->assertStatus(405);
        $this->delete('/api/v1/health/readiness')->assertStatus(405);
        $this->patch('/api/v1/health/readiness')->assertStatus(405);
    }

    /**
     * Test that response is valid JSON.
     */
    public function test_readiness_check_returns_valid_json(): void
    {
        $response = $this->get('/api/v1/health/readiness');

        $response->assertHeader('Content-Type', 'application/json');

        $data = $response->json();
        $this->assertIsArray($data);
    }

    /**
     * Test response time is fast (important for Kubernetes).
     */
    public function test_readiness_check_responds_quickly(): void
    {
        $startTime = microtime(true);

        $response = $this->get('/api/v1/health/readiness');

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Kubernetes probes should be fast
        // Should respond within 1 second
        $this->assertLessThan(1000, $executionTime, 'Readiness check should respond within 1 second');
    }

    /**
     * Test that endpoint can be called repeatedly (as Kubernetes does).
     */
    public function test_readiness_check_can_be_called_repeatedly(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->get('/api/v1/health/readiness');
            $this->assertContains($response->status(), [200, 503]);

            $data = $response->json();
            $this->assertArrayHasKey('ready', $data);
            $this->assertIsBool($data['ready']);
        }
    }

    /**
     * Test that checks field contains relevant dependencies.
     */
    public function test_checks_field_contains_relevant_dependencies(): void
    {
        $response = $this->get('/api/v1/health/readiness');

        $data = $response->json();

        // Readiness should check database and cache (most common critical dependencies)
        $checkNames = array_keys($data['checks']);

        // Should have at least one check
        $this->assertNotEmpty($checkNames, 'Should have at least one readiness check');
    }
}
