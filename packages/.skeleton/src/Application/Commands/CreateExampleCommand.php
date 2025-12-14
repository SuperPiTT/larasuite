<?php

declare(strict_types=1);

namespace Larasuite\Skeleton\Application\Commands;

/**
 * Command for creating a new Example entity.
 *
 * Commands represent intentions to change state. They are immutable
 * and contain all the data needed to perform the operation.
 */
final readonly class CreateExampleCommand
{
    public function __construct(
        public string $name,
    ) {}
}
