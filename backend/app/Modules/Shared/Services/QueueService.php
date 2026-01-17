<?php

namespace Modules\Shared\Services;

use Illuminate\Bus\Queueable;
use Modules\Shared\Support\QueuePriority;

/**
 * Queue Service
 *
 * Centralized service for dispatching jobs to priority queues.
 * Provides a consistent API for queue priority management across all modules.
 *
 * @package Modules\Shared\Services
 * @version 1.0.0
 */
class QueueService
{
    /**
     * Dispatch a job to a specific priority queue
     *
     * @param object $job The job instance to dispatch
     * @param string $priority The priority level (use QueuePriority constants)
     * @return mixed The dispatch result
     * @throws \InvalidArgumentException If priority is invalid
     */
    public function dispatchToPriority(object $job, string $priority): mixed
    {
        if (!QueuePriority::isValid($priority)) {
            throw new \InvalidArgumentException(
                "Invalid queue priority: {$priority}. Valid priorities are: " . implode(', ', QueuePriority::ALL)
            );
        }

        // If job uses Queueable trait, set the queue property
        if (in_array(Queueable::class, class_uses_recursive($job))) {
            return dispatch($job)->onQueue($priority);
        }

        // Otherwise, use onQueue method if available
        if (method_exists($job, 'onQueue')) {
            return $job->onQueue($priority);
        }

        // Fallback to standard dispatch with onQueue
        return dispatch($job)->onQueue($priority);
    }

    /**
     * Dispatch a job to the critical priority queue
     *
     * @param object $job The job instance to dispatch
     * @return mixed The dispatch result
     */
    public function dispatchCritical(object $job): mixed
    {
        return $this->dispatchToPriority($job, QueuePriority::CRITICAL);
    }

    /**
     * Dispatch a job to the high priority queue
     *
     * @param object $job The job instance to dispatch
     * @return mixed The dispatch result
     */
    public function dispatchHigh(object $job): mixed
    {
        return $this->dispatchToPriority($job, QueuePriority::HIGH);
    }

    /**
     * Dispatch a job to the medium priority queue
     *
     * @param object $job The job instance to dispatch
     * @return mixed The dispatch result
     */
    public function dispatchMedium(object $job): mixed
    {
        return $this->dispatchToPriority($job, QueuePriority::MEDIUM);
    }

    /**
     * Dispatch a job to the default priority queue
     *
     * @param object $job The job instance to dispatch
     * @return mixed The dispatch result
     */
    public function dispatchDefault(object $job): mixed
    {
        return $this->dispatchToPriority($job, QueuePriority::DEFAULT);
    }

    /**
     * Dispatch a job to the low priority queue
     *
     * @param object $job The job instance to dispatch
     * @return mixed The dispatch result
     */
    public function dispatchLow(object $job): mixed
    {
        return $this->dispatchToPriority($job, QueuePriority::LOW);
    }
}

