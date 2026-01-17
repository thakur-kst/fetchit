<?php

namespace Modules\Shared\Support;

/**
 * Queue Priority Constants
 *
 * Defines the 5 priority levels for queue jobs.
 * Used across all modules for consistent queue priority handling.
 *
 * @package Modules\Shared\Support
 * @version 1.0.0
 */
class QueuePriority
{
    /**
     * Critical priority - highest priority, most urgent jobs
     * Examples: Payment processing, critical notifications
     */
    public const CRITICAL = 'critical';

    /**
     * High priority - important jobs that should be processed quickly
     * Examples: Order processing, important notifications
     */
    public const HIGH = 'high';

    /**
     * Medium priority - standard background jobs
     * Examples: Balance syncs, data synchronization
     */
    public const MEDIUM = 'medium';

    /**
     * Default priority - standard queue priority
     * Examples: General background tasks
     */
    public const DEFAULT = 'default';

    /**
     * Low priority - non-urgent background tasks
     * Examples: Analytics, reporting, cleanup jobs
     */
    public const LOW = 'low';

    /**
     * All available priority levels
     *
     * @var array<string>
     */
    public const ALL = [
        self::CRITICAL,
        self::HIGH,
        self::MEDIUM,
        self::DEFAULT,
        self::LOW,
    ];

    /**
     * Priority order (highest to lowest)
     *
     * @var array<string>
     */
    public const ORDER = [
        self::CRITICAL,
        self::HIGH,
        self::MEDIUM,
        self::DEFAULT,
        self::LOW,
    ];

    /**
     * Check if a priority level is valid
     *
     * @param string $priority
     * @return bool
     */
    public static function isValid(string $priority): bool
    {
        return in_array($priority, self::ALL, true);
    }

    /**
     * Get priority order index (0 = highest, 4 = lowest)
     *
     * @param string $priority
     * @return int|null Returns null if priority is invalid
     */
    public static function getOrder(string $priority): ?int
    {
        $index = array_search($priority, self::ORDER, true);
        return $index !== false ? $index : null;
    }

    /**
     * Compare two priorities
     *
     * @param string $priority1
     * @param string $priority2
     * @return int Returns -1 if priority1 is higher, 1 if priority2 is higher, 0 if equal
     */
    public static function compare(string $priority1, string $priority2): int
    {
        $order1 = self::getOrder($priority1);
        $order2 = self::getOrder($priority2);

        if ($order1 === null || $order2 === null) {
            return 0;
        }

        return $order1 <=> $order2;
    }
}

