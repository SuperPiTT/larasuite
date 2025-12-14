<?php

declare(strict_types=1);

namespace Larasuite\Skeleton\Domain\ValueObjects;

use InvalidArgumentException;

/**
 * Example Value Object representing an entity ID.
 *
 * Value Objects are immutable and compared by value, not identity.
 * They encapsulate validation and domain-specific logic.
 */
final readonly class ExampleId implements \Stringable
{
    private function __construct(
        private int $value,
    ) {
        if ($value <= 0) {
            throw new InvalidArgumentException('ID must be a positive integer');
        }
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
