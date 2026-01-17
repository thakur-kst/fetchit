<?php

namespace Modules\Shared\Services;

use Illuminate\Http\Request;

/**
 * IP Whitelist Service
 *
 * IP whitelist management service.
 * Supports module-specific and global whitelists.
 *
 * @package Modules\Shared\Services
 * @version 1.0.0
 */
class IPWhitelistService
{
    /**
     * Check if IP is whitelisted
     *
     * @param string $ip
     * @param string|null $module
     * @return bool
     */
    public function isWhitelisted(string $ip, ?string $module = null): bool
    {
        $config = config('shared.ip_whitelist', []);

        // If whitelisting is disabled, allow all
        if (!($config['enabled'] ?? false)) {
            return true;
        }

        // Check global whitelist
        $globalIps = $config['ips'] ?? [];
        if (in_array($ip, $globalIps, true)) {
            return true;
        }

        // Check module-specific whitelist
        if ($module && isset($config['by_module'][$module])) {
            $moduleIps = $config['by_module'][$module]['ips'] ?? [];
            if (in_array($ip, $moduleIps, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if route requires whitelist for module
     *
     * @param string $routeName
     * @param string $module
     * @return bool
     */
    public function routeRequiresWhitelist(string $routeName, string $module): bool
    {
        $config = config('shared.ip_whitelist', []);

        if (!isset($config['by_module'][$module])) {
            return false;
        }

        $moduleConfig = $config['by_module'][$module];
        $requiredRoutes = $moduleConfig['routes'] ?? [];

        return in_array($routeName, $requiredRoutes, true);
    }

    /**
     * Get whitelisted IPs for a module
     *
     * @param string|null $module
     * @return array
     */
    public function getWhitelistedIps(?string $module = null): array
    {
        $config = config('shared.ip_whitelist', []);

        if ($module && isset($config['by_module'][$module])) {
            return $config['by_module'][$module]['ips'] ?? [];
        }

        return $config['ips'] ?? [];
    }
}

