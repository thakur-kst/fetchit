<?php

namespace Modules\Shared\Traits;

use Modules\Shared\Support\QueuePriority;

/**
 * Queueable Priority Trait
 *
 * Allows job classes to define a default queue priority.
 * Jobs using this trait should define a $queuePriority property in the job class.
 * The trait provides helper methods to initialize and manage queue priority.
 *
 * Example:
 * ```php
 * class MyJob implements ShouldQueue
 * {
 *     use QueueablePriority;
 *     
 *     // Define queuePriority in your job class
 *     public string $queuePriority = QueuePriority::MEDIUM;
 *     
 *     public function __construct()
 *     {
 *         $this->initializeQueuePriority();
 *     }
 * }
 * ```
 *
 * @package Modules\Shared\Traits
 * @version 1.0.0
 */
trait QueueablePriority
{
    /**
     * Initialize the queue property based on queuePriority.
     * Call this method in your job's constructor to automatically set the queue.
     * 
     * Requires that the job class defines a $queuePriority property.
     *
     * @return void
     */
    protected function initializeQueuePriority(): void
    {
        // Check if queuePriority property exists
        if (!property_exists($this, 'queuePriority')) {
            // If not defined, set default
            $this->queue = QueuePriority::DEFAULT;
            return;
        }

        if (QueuePriority::isValid($this->queuePriority)) {
            $this->queue = $this->queuePriority;
        } else {
            $this->queue = QueuePriority::DEFAULT;
            $this->queuePriority = QueuePriority::DEFAULT;
        }
    }

    /**
     * Set the queue priority
     * 
     * Requires that the job class defines a $queuePriority property.
     *
     * @param string $priority
     * @return $this
     */
    public function setPriority(string $priority): self
    {
        if (QueuePriority::isValid($priority)) {
            // Set queuePriority if property exists
            if (property_exists($this, 'queuePriority')) {
                $this->queuePriority = $priority;
            }
            $this->queue = $priority;
        }

        return $this;
    }
}

