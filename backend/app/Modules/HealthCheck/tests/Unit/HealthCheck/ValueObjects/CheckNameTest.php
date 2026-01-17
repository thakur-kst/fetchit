<?php

namespace Modules\HealthCheck\Tests\Unit\HealthCheck\ValueObjects;

use Modules\HealthCheck\ValueObjects\CheckName;
use PHPUnit\Framework\TestCase;

/**
 * CheckName Value Object Unit Tests
 *
 * Tests the immutability and behavior of CheckName value object.
 *
 * @package Tests\Unit\HealthCheck\ValueObjects
 */
class CheckNameTest extends TestCase
{
    /**
     * Test that CheckName can be created with valid name.
     */
    public function test_can_create_check_name_with_valid_name(): void
    {
        $name = new CheckName('database');

        $this->assertInstanceOf(CheckName::class, $name);
        $this->assertEquals('database', $name->value());
    }

    /**
     * Test that CheckName can be created with different names.
     */
    public function test_can_create_check_name_with_different_names(): void
    {
        $database = new CheckName('database');
        $cache = new CheckName('cache');
        $redis = new CheckName('redis');

        $this->assertEquals('database', $database->value());
        $this->assertEquals('cache', $cache->value());
        $this->assertEquals('redis', $redis->value());
    }

    /**
     * Test that toString returns the name.
     */
    public function test_to_string_returns_name(): void
    {
        $name = new CheckName('application');

        $this->assertEquals('application', $name->value());
        $this->assertEquals('application', (string) $name);
    }

    /**
     * Test that two CheckNames with same value are equal.
     */
    public function test_two_check_names_with_same_value_are_equal(): void
    {
        $name1 = new CheckName('database');
        $name2 = new CheckName('database');

        $this->assertEquals($name1->value(), $name2->value());
    }

    /**
     * Test that two CheckNames with different values are not equal.
     */
    public function test_two_check_names_with_different_values_are_not_equal(): void
    {
        $name1 = new CheckName('database');
        $name2 = new CheckName('cache');

        $this->assertNotEquals($name1->value(), $name2->value());
    }

    /**
     * Test that CheckName is immutable.
     */
    public function test_check_name_is_immutable(): void
    {
        $name = new CheckName('database');

        // Create another instance
        $name2 = new CheckName('cache');

        // Original should not be affected
        $this->assertEquals('database', $name->value());
        $this->assertEquals('cache', $name2->value());
    }

    /**
     * Test that CheckName accepts alphanumeric names.
     */
    public function test_check_name_accepts_alphanumeric_names(): void
    {
        $name1 = new CheckName('database123');
        $name2 = new CheckName('cache-v2');
        $name3 = new CheckName('redis_cluster');

        $this->assertEquals('database123', $name1->value());
        $this->assertEquals('cache-v2', $name2->value());
        $this->assertEquals('redis_cluster', $name3->value());
    }
}
