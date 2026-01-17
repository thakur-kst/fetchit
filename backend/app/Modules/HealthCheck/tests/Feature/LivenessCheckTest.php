<?php

namespace Modules\HealthCheck\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Liveness Check Endpoint Tests
 *
 * Tests the GET /api/v1/health/liveness endpoint functionality.
 * This endpoint is typically used by Kubernetes liveness probes.
 *
 * @package Tests\Feature\HealthCheck
 */
class LivenessCheckTest extends TestCase
{
    /**
     * Test that liveness check endpoint is accessible.
     */
    public function test_liveness_check_endpoint_is_accessible(): void
    {
        $response = $this->get('/api/v1/health/liveness');

        $response->assertStatus(200);
    }

    /**
     * Test that liveness check returns correct JSON structure.
     */
    public function test_liveness_check_returns_correct_json_structure(): void
    {
        $response = $this->get('/api/v1/health/liveness');

        $response->assertJsonStructure([
            'alive',
            'timestamp',
        ]);
    }

    /**
     * Test that alive field is boolean.
     */
    public function test_alive_field_is_boolean(): void
    {
        $response = $this->get('/api/v1/health/liveness');

        $data = $response->json();

        $this->assertIsBool($data['alive'], 'alive field should be boolean');
    }

    /**
     * Test that liveness check always returns true (application is running).
     */
    public function test_liveness_check_returns_alive_true(): void
    {
        $response = $this->get('/api/v1/health/liveness');

        $response->assertStatus(200);
        $response->assertJson([
            'alive' => true,
        ]);
    }

    /**
     * Test that timestamp is in ISO 8601 format.
     */
    public function test_liveness_check_timestamp_is_valid_iso8601(): void
    {
        $response = $this->get('/api/v1/health/liveness');

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
     * Test that liveness check does not check external dependencies.
     */
    public function test_liveness_check_does_not_include_dependency_checks(): void
    {
        $response = $this->get('/api/v1/health/liveness');

        $data = $response->json();

        // Liveness should NOT check database, cache, etc.
        // Should only check if application can respond
        $this->assertArrayNotHasKey('checks', $data, 'Liveness should not include dependency checks');
    }

    /**
     * Test that endpoint does not require authentication.
     */
    public function test_liveness_check_does_not_require_authentication(): void
    {
        $response = $this->get('/api/v1/health/liveness');

        $response->assertStatus(200);
        $response->assertJson([
            'alive' => true,
        ]);
    }

    /**
     * Test that endpoint accepts only GET method.
     */
    public function test_liveness_check_only_accepts_get_method(): void
    {
        $this->get('/api/v1/health/liveness')->assertStatus(200);
        $this->post('/api/v1/health/liveness')->assertStatus(405);
        $this->put('/api/v1/health/liveness')->assertStatus(405);
        $this->delete('/api/v1/health/liveness')->assertStatus(405);
        $this->patch('/api/v1/health/liveness')->assertStatus(405);
    }

    /**
     * Test that response is valid JSON.
     */
    public function test_liveness_check_returns_valid_json(): void
    {
        $response = $this->get('/api/v1/health/liveness');

        $response->assertHeader('Content-Type', 'application/json');

        $data = $response->json();
        $this->assertIsArray($data);
    }

    /**
     * Test response time is very fast (critical for liveness).
     */
    public function test_liveness_check_responds_very_quickly(): void
    {
        $startTime = microtime(true);

        $response = $this->get('/api/v1/health/liveness');

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);

        // Liveness should be VERY fast (no external checks)
        // Should respond within 50ms
        $this->assertLessThan(50, $executionTime, 'Liveness check should respond within 50ms');
    }

    /**
     * Test that endpoint can be called very frequently (as Kubernetes does).
     */
    public function test_liveness_check_can_be_called_very_frequently(): void
    {
        // Simulate frequent Kubernetes liveness probe calls
        for ($i = 0; $i < 10; $i++) {
            $response = $this->get('/api/v1/health/liveness');

            $response->assertStatus(200);
            $response->assertJson([
                'alive' => true,
            ]);
        }
    }

    /**
     * Test that response is consistent across multiple calls.
     */
    public function test_liveness_check_response_is_consistent(): void
    {
        $response1 = $this->get('/api/v1/health/liveness');
        $response2 = $this->get('/api/v1/health/liveness');
        $response3 = $this->get('/api/v1/health/liveness');

        $data1 = $response1->json();
        $data2 = $response2->json();
        $data3 = $response3->json();

        // All should return alive: true
        $this->assertTrue($data1['alive']);
        $this->assertTrue($data2['alive']);
        $this->assertTrue($data3['alive']);

        // All should have status 200
        $response1->assertStatus(200);
        $response2->assertStatus(200);
        $response3->assertStatus(200);
    }

    /**
     * Test that liveness check has minimal response payload.
     */
    public function test_liveness_check_has_minimal_response_payload(): void
    {
        $response = $this->get('/api/v1/health/liveness');

        $data = $response->json();

        // Should only have 2 fields: alive and timestamp
        $this->assertCount(2, $data, 'Liveness response should have only 2 fields');
        $this->assertArrayHasKey('alive', $data);
        $this->assertArrayHasKey('timestamp', $data);
    }

    /**
     * Test that liveness check never returns 503.
     */
    public function test_liveness_check_never_returns_503(): void
    {
        // Call multiple times to ensure consistency
        for ($i = 0; $i < 5; $i++) {
            $response = $this->get('/api/v1/health/liveness');
            $response->assertStatus(200);
        }
    }

    /**
     * Test concurrent liveness checks.
     */
    public function test_liveness_check_handles_concurrent_requests(): void
    {
        $responses = [];

        // Simulate concurrent requests (in sequence due to test limitations)
        for ($i = 0; $i < 5; $i++) {
            $responses[] = $this->get('/api/v1/health/liveness');
        }

        // All should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
            $response->assertJson(['alive' => true]);
        }
    }

    /**
     * Test that response has correct content type header.
     */
    public function test_liveness_check_returns_json_content_type(): void
    {
        $response = $this->get('/api/v1/health/liveness');

        $response->assertHeader('Content-Type', 'application/json');
    }
}
