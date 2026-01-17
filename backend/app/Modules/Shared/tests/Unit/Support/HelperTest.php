<?php

namespace Modules\Shared\Tests\Unit\Support;

use Modules\Shared\Support\Helper;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    public function test_generate_transaction_id_has_correct_format(): void
    {
        $suffix = 'GL11088';
        $transactionId = Helper::generateTransactionId($suffix);

        // Format: TXN{His}{12 hex chars}-{suffix}
        // TXN = prefix, His = 6 digits (HHMMSS), 12 hex chars from 6 random bytes, then suffix
        $this->assertMatchesRegularExpression(
            '/^TXN\d{6}[0-9a-f]{12}-GL11088$/',
            $transactionId
        );

        // Verify it starts with TXN
        $this->assertStringStartsWith('TXN', $transactionId);

        // Verify it ends with the suffix
        $this->assertStringEndsWith('-GL11088', $transactionId);

        // Verify the structure: TXN + 6 digits + 12 hex + dash + suffix
        $this->assertEquals(29, strlen($transactionId)); // TXN(3) + 6 + 12 + 1(dash) + 7(suffix) = 29
    }

    public function test_generate_transaction_id_throws_for_empty_suffix(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaction ID suffix must not be empty.');

        Helper::generateTransactionId('   ');
    }

    public function test_generate_transaction_id_throws_for_insufficient_random_bytes(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Helper::generateTransactionId('GL11088', 2);
    }
}

