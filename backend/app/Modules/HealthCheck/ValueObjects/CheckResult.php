<?php

namespace Modules\HealthCheck\ValueObjects;

/**
 * Check Result Value Object
 *
 * Represents the result of a health check
 */
final class CheckResult
{
    private CheckName $name;
    private HealthyStatus $status;
    private string $message;
    private ?array $details;
    private ?string $error;
    private ?float $responseTimeMs;

    public function __construct(
        CheckName $name,
        HealthyStatus $status,
        string $message,
        ?array $details = null,
        ?string $error = null,
        ?float $responseTimeMs = null
    ) {
        $this->name = $name;
        $this->status = $status;
        $this->message = $message;
        $this->details = $details;
        $this->error = $error;
        $this->responseTimeMs = $responseTimeMs;
    }

    public static function healthy(
        CheckName $name,
        string $message,
        ?array $details = null,
        ?float $responseTimeMs = null
    ): self {
        return new self(
            $name,
            HealthyStatus::healthy(),
            $message,
            $details,
            null,
            $responseTimeMs
        );
    }

    public static function unhealthy(
        CheckName $name,
        string $message,
        string $error,
        ?array $details = null
    ): self {
        return new self(
            $name,
            HealthyStatus::unhealthy(),
            $message,
            $details,
            $error,
            null
        );
    }

    public function getName(): CheckName
    {
        return $this->name;
    }

    public function getStatus(): HealthyStatus
    {
        return $this->status;
    }

    public function isHealthy(): bool
    {
        return $this->status->isHealthy();
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getDetails(): ?array
    {
        return $this->details;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getResponseTimeMs(): ?float
    {
        return $this->responseTimeMs;
    }

    public function toArray(): array
    {
        $result = [
            'status' => $this->status->value(),
            'message' => $this->message,
        ];

        if ($this->details !== null) {
            if ($this->responseTimeMs !== null) {
                $this->details['response_time_ms'] = $this->responseTimeMs;
            }
            $result['details'] = $this->details;
        }

        if ($this->error !== null) {
            $result['error'] = $this->error;
        }

        return $result;
    }
}

