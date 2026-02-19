<?php

declare(strict_types=1);

namespace spec\Flagbit\Bundle\CategoryBundle\Controller\InternalApi;

use Akeneo\Category\Infrastructure\Component\Model\CategoryInterface;
use Akeneo\Category\Infrastructure\Component\Classification\Repository\CategoryRepositoryInterface;
use Flagbit\Bundle\CategoryBundle\Controller\InternalApi\CategoryPropertyController;
use Flagbit\Bundle\CategoryBundle\Entity\CategoryProperty;
use Flagbit\Bundle\CategoryBundle\Repository\CategoryPropertyRepository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CategoryPropertyControllerSpec extends ObjectBehavior
{
    public function let(
        CategoryPropertyRepository $repository,
        CategoryRepositoryInterface $categoryRepository,
        NormalizerInterface $normalizer
    ): void {
        $this->beConstructedWith($repository, $categoryRepository, $normalizer);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(CategoryPropertyController::class);
    }

    public function it_is_new_category_property_for_category(
        CategoryPropertyRepository $repository,
        CategoryRepositoryInterface $categoryRepository,
        NormalizerInterface $normalizer,
        CategoryInterface $category
    ): void {
        $categoryRepository->findOneByIdentifier(1)->willReturn($category);

        $repository->findOneBy(['category' => $category])->willReturn(null);

        $context = [AbstractNormalizer::IGNORED_ATTRIBUTES => ['category']];
        $normalizer->normalize(Argument::type(CategoryProperty::class), 'internal_api', $context)
            ->willReturn([]);

        $this->get(1)->shouldBeLike(new JsonResponse([]));
    }

    public function it_finds_category_property_for_category(
        CategoryPropertyRepository $repository,
        CategoryRepositoryInterface $categoryRepository,
        NormalizerInterface $normalizer,
        CategoryInterface $category,
        CategoryProperty $categoryProperty
    ): void {
        $categoryRepository->findOneByIdentifier(1)->willReturn($category);

        $repository->findOneBy(['category' => $category])->willReturn($categoryProperty);

        $context = [AbstractNormalizer::IGNORED_ATTRIBUTES => ['category']];
        $normalizer->normalize($categoryProperty, 'internal_api', $context)
            ->willReturn([]);

        $this->get(1)->shouldBeLike(new JsonResponse([]));
    }
}
