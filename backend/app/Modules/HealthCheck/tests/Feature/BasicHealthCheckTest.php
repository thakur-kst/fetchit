<?php

namespace Modules\HealthCheck\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Basic Health Check Endpoint Tests
 *
 * Tests the GET /api/v1/health endpoint functionality.
 *
 * @package Tests\Feature\HealthCheck
 */
class BasicHealthCheckTest extends TestCase
{
    /**
     * Test that basic health check endpoint returns successful response.
     */
    public function test_basic_health_check_returns_successful_response(): void
    {
        $response = $this->get('/api/v1/health');

        $response->assertStatus(200);
    }

    /**
     * Test that basic health check returns correct JSON structure.
     */
    public function test_basic_health_check_returns_correct_json_structure(): void
    {
        $response = $this->get('/api/v1/health');

        $response->assertJsonStructure([
            'status',
            'timestamp',
            'service',
            'environment',
        ]);
    }

    /**
     * Test that basic health check returns healthy status.
     */
    public function test_basic_health_check_returns_healthy_status(): void
    {
        $response = $this->get('/api/v1/health');

        $response->assertJson([
            'status' => 'healthy',
            'service' => 'CustomerPortal',
        ]);
    }

    /**
     * Test that timestamp is in ISO 8601 format.
     */
    public function test_basic_health_check_timestamp_is_valid_iso8601(): void
    {
        $response = $this->get('/api/v1/health');

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
     * Test that environment is returned correctly.
     */
    public function test_basic_health_check_returns_current_environment(): void
    {
        $response = $this->get('/api/v1/health');

        $data = $response->json();

        $this->assertArrayHasKey('environment', $data);
        $this->assertEquals(config('app.env'), $data['environment']);
    }

    /**
     * Test that response has correct content type.
     */
    public function test_basic_health_check_returns_json_content_type(): void
    {
        $response = $this->get('/api/v1/health');

        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Test that endpoint does not require authentication.
     */
    public function test_basic_health_check_does_not_require_authentication(): void
    {
        // Should work without authentication
        $response = $this->get('/api/v1/health');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'healthy',
        ]);
    }

    /**
     * Test that endpoint responds quickly (performance test).
     */
    public function test_basic_health_check_responds_quickly(): void
    {
        $startTime = microtime(true);

        $response = $this->get('/api/v1/health');

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);

        // Health check should respond within 100ms
        $this->assertLessThan(100, $executionTime, 'Health check should respond within 100ms');
    }

    /**
     * Test that endpoint can be called multiple times.
     */
    public function test_basic_health_check_can_be_called_multiple_times(): void
    {
        $response1 = $this->get('/api/v1/health');
        $response2 = $this->get('/api/v1/health');
        $response3 = $this->get('/api/v1/health');

        $response1->assertStatus(200);
        $response2->assertStatus(200);
        $response3->assertStatus(200);

        // All should return healthy
        $response1->assertJson(['status' => 'healthy']);
        $response2->assertJson(['status' => 'healthy']);
        $response3->assertJson(['status' => 'healthy']);
    }

    /**
     * Test that endpoint accepts only GET method.
     */
    public function test_basic_health_check_only_accepts_get_method(): void
    {
        $this->get('/api/v1/health')->assertStatus(200);
        $this->post('/api/v1/health')->assertStatus(405);
        $this->put('/api/v1/health')->assertStatus(405);
        $this->delete('/api/v1/health')->assertStatus(405);
        $this->patch('/api/v1/health')->assertStatus(405);
    }
}
