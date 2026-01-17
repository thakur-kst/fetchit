<?php

namespace Modules\Shared\Traits;

/**
 * Response Wrapper Trait
 *
 * Provides helper methods for controllers to override response status.
 *
 * @package Modules\Shared\Traits
 * @version 1.0.0
 */
trait ResponseWrapperTrait
{
    /**
     * Set response status override
     *
     * This status will be used instead of the HTTP status code
     * when the response is wrapped.
     *
     * @param int $status
     * @return void
     */
    protected function setResponseStatus(int $status): void
    {
        request()->attributes->set('response_status_override', $status);
    }

    /**
     * Clear response status override
     *
     * @return void
     */
    protected function clearResponseStatus(): void
    {
        request()->attributes->remove('response_status_override');
    }
}

