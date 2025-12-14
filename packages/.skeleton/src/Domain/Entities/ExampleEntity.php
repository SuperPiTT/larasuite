<?php

declare(strict_types=1);

namespace Larasuite\Skeleton\Domain\Entities;

use Larasuite\Skeleton\Domain\ValueObjects\ExampleId;

/**
 * Example Domain Entity.
 *
 * Domain entities encapsulate business logic and are the core of your domain.
 * They should be framework-agnostic and contain only business rules.
 */
final readonly class ExampleEntity
{
    public function __construct(
        private ExampleId $id,
        private string $name,
        private \DateTimeImmutable $createdAt,
    ) {}

    public function id(): ExampleId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Create a new instance with updated name.
     */
    public function withName(string $name): self
    {
        return new self(
            id: $this->id,
            name: $name,
            createdAt: $this->createdAt,
        );
    }
}
