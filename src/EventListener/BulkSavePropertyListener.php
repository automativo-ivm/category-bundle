<?php

declare(strict_types=1);

namespace Flagbit\Bundle\CategoryBundle\EventListener;

use Akeneo\Category\Infrastructure\Component\Model\CategoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Flagbit\Bundle\CategoryBundle\Exception\ValidationFailed;
use Flagbit\Bundle\CategoryBundle\Repository\CategoryPropertyRepository;
use Flagbit\Bundle\CategoryBundle\Schema\SchemaValidator;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\ParameterBag;

use function count;

/**
 * Category post save listener that handles bulk saves with properties.
 */
class BulkSavePropertyListener
{
    /** @var ParameterBag<string, array<string, mixed>> */
    private ParameterBag $propertiesBag;
    private CategoryPropertyRepository $repository;
    private EntityManagerInterface $entityManager;
    private SchemaValidator $validator;

    /**
     * @phpstan-param ParameterBag<string, array<string, mixed>> $propertiesBag
     */
    public function __construct(
        ParameterBag $propertiesBag,
        CategoryPropertyRepository $repository,
        EntityManagerInterface $entityManager,
        SchemaValidator $validator
    ) {
        $this->propertiesBag = $propertiesBag;
        $this->repository    = $repository;
        $this->entityManager = $entityManager;
        $this->validator     = $validator;
    }

    /**
     * @phpstan-param GenericEvent<mixed> $event
     *
     * @throws ValidationFailed
     */
    public function onBulkCategoryPostSave(GenericEvent $event): void
    {
        // Save category properties
        foreach ($event->getSubject() as $category) {
            if (! $category instanceof CategoryInterface) {
                continue;
            }

            if (! $this->propertiesBag->has($category->getCode())) {
                continue;
            }

            $properties = $this->propertiesBag->get($category->getCode());
            if (count($properties) === 0) {
                return;
            }

            if ($this->validator->validate($properties) !== []) {
                throw ValidationFailed::invalidPropertyJsonFormat();
            }

            $categoryProperty = $this->repository->findOrCreateByCategory($category);

            $categoryProperty->mergeProperties($properties);

            $this->entityManager->persist($categoryProperty);
            $this->entityManager->flush();
        }
    }
}
