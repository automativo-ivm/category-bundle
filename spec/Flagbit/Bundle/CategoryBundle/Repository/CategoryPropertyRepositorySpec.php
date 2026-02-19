<?php

declare(strict_types=1);

namespace spec\Flagbit\Bundle\CategoryBundle\Repository;

use Akeneo\Category\Infrastructure\Component\Model\CategoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\UnitOfWork;
use Flagbit\Bundle\CategoryBundle\Entity\CategoryProperty;
use Flagbit\Bundle\CategoryBundle\Repository\CategoryPropertyRepository;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class CategoryPropertyRepositorySpec extends ObjectBehavior
{
    public function let(EntityManagerInterface $em, ClassMetadata $class): void
    {
        $this->beConstructedWith($em, $class);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(CategoryPropertyRepository::class);
    }

    public function it_finds_by_category(
        CategoryInterface $category,
        CategoryProperty $categoryProperty,
        EntityManagerInterface $em,
        UnitOfWork $unitOfWork,
        EntityPersister $entityPersister
    ): void {
        $entityPersister->load(['category' => $category], null, null, [], null, 1, null)
            ->willReturn($categoryProperty);
        $unitOfWork->getEntityPersister(Argument::any())->willReturn($entityPersister);
        $em->getUnitOfWork()->willReturn($unitOfWork);

        $this->findByCategory($category)->shouldReturn($categoryProperty);
    }

    public function it_finds_instead_of_create_by_category(
        CategoryInterface $category,
        CategoryProperty $categoryProperty,
        EntityManagerInterface $em,
        UnitOfWork $unitOfWork,
        EntityPersister $entityPersister
    ): void {
        $entityPersister->load(['category' => $category], null, null, [], null, 1, null)
            ->willReturn($categoryProperty);
        $unitOfWork->getEntityPersister(Argument::any())->willReturn($entityPersister);
        $em->getUnitOfWork()->willReturn($unitOfWork);

        $this->findOrCreateByCategory($category)->shouldReturn($categoryProperty);
    }

    public function it_creates_instead_of_find_by_category(
        CategoryInterface $category,
        EntityManagerInterface $em,
        UnitOfWork $unitOfWork,
        EntityPersister $entityPersister
    ): void {
        $entityPersister->load(['category' => $category], null, null, [], null, 1, null)
            ->willReturn(null);
        $unitOfWork->getEntityPersister(Argument::any())->willReturn($entityPersister);
        $em->getUnitOfWork()->willReturn($unitOfWork);

        $this->findOrCreateByCategory($category)->shouldBeAnInstanceOf(CategoryProperty::class);
    }
}
