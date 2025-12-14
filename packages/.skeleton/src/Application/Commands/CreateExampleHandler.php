<?php

declare(strict_types=1);

namespace Larasuite\Skeleton\Application\Commands;

use Larasuite\Skeleton\Domain\Entities\ExampleEntity;
use Larasuite\Skeleton\Domain\Repositories\ExampleRepositoryInterface;
use Larasuite\Skeleton\Domain\ValueObjects\ExampleId;

/**
 * Handler for CreateExampleCommand.
 *
 * Command handlers contain the application logic for executing commands.
 * They orchestrate domain objects and infrastructure services.
 */
final readonly class CreateExampleHandler
{
    public function __construct(
        private ExampleRepositoryInterface $repository,
    ) {}

    public function handle(CreateExampleCommand $command): ExampleEntity
    {
        $entity = new ExampleEntity(
            id: ExampleId::fromInt(1), // In real implementation, generate ID
            name: $command->name,
            createdAt: new \DateTimeImmutable(),
        );

        $this->repository->save($entity);

        return $entity;
    }
}
