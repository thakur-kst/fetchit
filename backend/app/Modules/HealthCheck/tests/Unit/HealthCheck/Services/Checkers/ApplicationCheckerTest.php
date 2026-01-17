<?php

namespace Modules\HealthCheck\Tests\Unit\HealthCheck\Services\Checkers;

use Modules\HealthCheck\Services\Checkers\ApplicationChecker;
use Modules\HealthCheck\ValueObjects\CheckName;
use Modules\HealthCheck\ValueObjects\CheckResult;
use PHPUnit\Framework\TestCase;

/**
 * ApplicationChecker Unit Tests
 *
 * Tests the ApplicationChecker component.
 *
 * @package Tests\Unit\HealthCheck\Services\Checkers
 */
class ApplicationCheckerTest extends TestCase
{
    private ApplicationChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new ApplicationChecker();
    }

    /**
     * Test that ApplicationChecker can be instantiated.
     */
    public function test_can_instantiate_application_checker(): void
    {
        $this->assertInstanceOf(ApplicationChecker::class, $this->checker);
    }

    /**
     * Test that ApplicationChecker has correct name.
     */
    public function test_application_checker_has_correct_name(): void
    {
        $result = $this->checker->check();
        $name = $result->getName();

        $this->assertInstanceOf(CheckName::class, $name);
        $this->assertEquals('application', $name->value());
    }

    /**
     * Test that ApplicationChecker check returns CheckResult.
     */
    public function test_check_returns_check_result(): void
    {
        $result = $this->checker->check();

        $this->assertInstanceOf(CheckResult::class, $result);
    }

    /**
     * Test that ApplicationChecker always returns healthy status.
     */
    public function test_application_checker_returns_healthy_status(): void
    {
        $result = $this->checker->check();

        // Application should always be healthy if code is running
        $this->assertTrue($result->isHealthy());
        $this->assertEquals('healthy', $result->getStatus()->value());
    }

    /**
     * Test that check result includes PHP version.
     */
    public function test_check_result_includes_php_version(): void
    {
        $result = $this->checker->check();

        $details = $result->getDetails();

        $this->assertArrayHasKey('php_version', $details);
        $this->assertEquals(PHP_VERSION, $details['php_version']);
    }

    /**
     * Test that check result includes Laravel version.
     */
    public function test_check_result_includes_laravel_version(): void
    {
        // This test requires Laravel app to be available
        // In pure unit test, we might skip this or use mock
        $this->markTestSkipped('Requires Laravel application context');
    }

    /**
     * Test that check can be called multiple times.
     */
    public function test_check_can_be_called_multiple_times(): void
    {
        $result1 = $this->checker->check();
        $result2 = $this->checker->check();
        $result3 = $this->checker->check();

        $this->assertTrue($result1->isHealthy());
        $this->assertTrue($result2->isHealthy());
        $this->assertTrue($result3->isHealthy());
    }

    /**
     * Test that checker is stateless (no side effects).
     */
    public function test_checker_is_stateless(): void
    {
        $result1 = $this->checker->check();
        $name1 = $result1->getName();

        $result2 = $this->checker->check();
        $name2 = $result2->getName();

        // Results should be independent
        $this->assertEquals($name1->value(), $name2->value());
        $this->assertEquals($result1->getStatus()->value(), $result2->getStatus()->value());
    }
}

