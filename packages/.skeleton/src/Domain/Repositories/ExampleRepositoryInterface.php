<?php

declare(strict_types=1);

namespace Larasuite\Skeleton\Domain\Repositories;

use Larasuite\Skeleton\Domain\Entities\ExampleEntity;
use Larasuite\Skeleton\Domain\ValueObjects\ExampleId;

/**
 * Repository Interface for ExampleEntity.
 *
 * Repositories abstract the persistence layer. The interface lives in the Domain
 * layer, while implementations live in the Infrastructure layer.
 */
interface ExampleRepositoryInterface
{
    public function findById(ExampleId $id): ?ExampleEntity;

    public function save(ExampleEntity $entity): void;

    public function delete(ExampleId $id): void;
}
