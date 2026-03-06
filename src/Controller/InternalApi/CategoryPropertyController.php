<?php

declare(strict_types=1);

namespace Flagbit\Bundle\CategoryBundle\Controller\InternalApi;

use Akeneo\Category\Infrastructure\Component\Classification\Repository\CategoryRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Flagbit\Bundle\CategoryBundle\Entity\CategoryProperty;
use Flagbit\Bundle\CategoryBundle\Repository\CategoryPropertyRepository;
use Flagbit\Bundle\CategoryBundle\Schema\SchemaValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

use function json_decode;

/**
 * @internal
 */
class CategoryPropertyController
{
    private CategoryPropertyRepository $repository;
    private NormalizerInterface $normalizer;
    private CategoryRepositoryInterface $categoryRepository;
    private EntityManagerInterface $entityManager;
    private SchemaValidator $validator;

    public function __construct(
        CategoryPropertyRepository $repository,
        CategoryRepositoryInterface $categoryRepository,
        NormalizerInterface $normalizer,
        EntityManagerInterface $entityManager,
        SchemaValidator $validator
    ) {
        $this->repository         = $repository;
        $this->categoryRepository = $categoryRepository;
        $this->normalizer         = $normalizer;
        $this->entityManager      = $entityManager;
        $this->validator          = $validator;
    }

    public function get(string $identifier): Response
    {
        $categoryProperty = $this->findProperty($identifier);

        $context = [AbstractNormalizer::IGNORED_ATTRIBUTES => ['category']];

        return new JsonResponse(
            $this->normalizer->normalize($categoryProperty, 'internal_api', $context)
        );
    }

    public function post(Request $request, string $identifier): Response
    {
        $category = $this->categoryRepository->findOneByIdentifier($identifier);
        if ($category === null) {
            return new JsonResponse(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        $properties = json_decode($request->getContent(), true);
        if ($properties === null) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->validator->validate($properties) !== []) {
            return new JsonResponse(['error' => 'Validation failed'], Response::HTTP_BAD_REQUEST);
        }

        /** @phpstan-var CategoryProperty|null $categoryProperty */
        $categoryProperty = $this->repository->findOneBy(['category' => $category]);
        if ($categoryProperty === null) {
            $categoryProperty = new CategoryProperty($category);
        }

        $categoryProperty->setProperties($properties);

        $this->entityManager->persist($categoryProperty);
        $this->entityManager->flush();

        return new JsonResponse([]);
    }

    private function findProperty(string $identifier): CategoryProperty
    {
        $category = $this->categoryRepository->findOneByIdentifier($identifier);

        /** @phpstan-var CategoryProperty|null $categoryProperty */
        $categoryProperty = $this->repository->findOneBy(['category' => $category]);
        if ($categoryProperty === null) {
            $categoryProperty = new CategoryProperty($category);
        }

        return $categoryProperty;
    }
}
