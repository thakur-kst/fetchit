<?php

namespace Modules\Shared\Support;

/**
 * Generic helper utilities for the Shared module.
 *
 * This class is intended to host commonly used helper methods that can be
 * reused across modules.
 */
class Helper
{
    /**
     * Generate a transaction ID with a secure random prefix and a provided suffix.
     *
     * Format: "[lowercase hex of length 2 * randomBytes]-[suffix]"
     *
     * Example (with default $randomBytes = 6):
     * - 12494e0136c5-GL11088
     *
     * @param string $suffix      Business-defined suffix to append after the random prefix.
     * @param int    $randomBytes Number of random bytes to generate for the prefix (>= 4 recommended).
     *
     * @return string
     *
     * @throws \InvalidArgumentException If suffix is empty or randomBytes is invalid.
     */
    public static function generateTransactionId(string $suffix, int $randomBytes = 6): string
    {
        $suffix = trim($suffix);

        if ($suffix === '') {
            throw new \InvalidArgumentException('Transaction ID suffix must not be empty.');
        }

        if ($randomBytes < 4) {
            // 4 bytes => 8 hex chars; anything smaller is not recommended for uniqueness.
            throw new \InvalidArgumentException('randomBytes must be at least 4 for sufficient entropy.');
        }

        // Generate cryptographically secure random bytes and convert to lowercase hex.
        $hexPrefix = bin2hex(random_bytes($randomBytes));

        return "TXN".now()->format('His')."{$hexPrefix}-{$suffix}";
    }

    /**
     * Generate a transaction ID using the default secure settings.
     *
     * Convenience wrapper for the most common pattern:
     * - 6 random bytes (12 hex characters) as prefix
     * - Caller-provided suffix
     *
     * @param string $suffix
     *
     * @return string
     */
    public static function generateDefaultTransactionId(string $suffix): string
    {
        return self::generateTransactionId($suffix, 6);
    }
}


