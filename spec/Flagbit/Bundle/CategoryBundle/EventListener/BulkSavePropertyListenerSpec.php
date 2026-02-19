<?php

declare(strict_types=1);

namespace spec\Flagbit\Bundle\CategoryBundle\EventListener;

use Akeneo\Category\Infrastructure\Component\Model\CategoryInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use EmptyIterator;
use Flagbit\Bundle\CategoryBundle\Entity\CategoryProperty;
use Flagbit\Bundle\CategoryBundle\EventListener\BulkSavePropertyListener;
use Flagbit\Bundle\CategoryBundle\Exception\ValidationFailed;
use Flagbit\Bundle\CategoryBundle\Repository\CategoryPropertyRepository;
use Flagbit\Bundle\CategoryBundle\Schema\SchemaValidator;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @method onBulkCategoryPostSave(GenericEvent $event)
 */
class BulkSavePropertyListenerSpec extends ObjectBehavior
{
    public function let(
        ParameterBag $propertiesBag,
        CategoryPropertyRepository $repository,
        EntityManagerInterface $entityManager,
        SchemaValidator $validator
    ): void {
        $this->beConstructedWith($propertiesBag, $repository, $entityManager, $validator);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(BulkSavePropertyListener::class);
    }

    public function it_ignores_entities_that_are_not_categories(
        GenericEvent $event,
        ParameterBag $propertiesBag
    ): void {
        $event->getSubject()->willReturn([new EmptyIterator(), new EmptyIterator()]);

        $propertiesBag->has(Argument::any())->shouldNotBeCalled();

        $this->onBulkCategoryPostSave($event);
    }

    public function it_ignores_categories_where_no_property_data_is_available(
        GenericEvent $event,
        ParameterBag $propertiesBag,
        CategoryInterface $category1,
        CategoryInterface $category2
    ): void {
        $event->getSubject()->willReturn([$category1, $category2]);
        $category1->getCode()->willReturn('electronics');
        $category2->getCode()->willReturn('clothes');

        $propertiesBag->has('electronics')->willReturn(false);
        $propertiesBag->has('clothes')->willReturn(false);

        $propertiesBag->get('electronics')->shouldNotHaveBeenCalled();
        $propertiesBag->get('clothes')->shouldNotHaveBeenCalled();

        $this->onBulkCategoryPostSave($event);
    }

    public function it_ignores_categories_where_property_data_is_empty(
        GenericEvent $event,
        ParameterBag $propertiesBag,
        CategoryPropertyRepository $repository,
        CategoryInterface $category1,
        CategoryInterface $category2
    ): void {
        $event->getSubject()->willReturn([$category1, $category2]);
        $category1->getCode()->willReturn('electronics');
        $category2->getCode()->willReturn('clothes');

        $propertiesBag->has('electronics')->willReturn(true);
        $propertiesBag->has('clothes')->willReturn(true);

        $propertiesBag->get('electronics')->willReturn([]);
        $propertiesBag->get('clothes')->willReturn([]);

        $repository->findOrCreateByCategory(Argument::any())->shouldNotHaveBeenCalled();

        $this->onBulkCategoryPostSave($event);
    }

    public function it_throws_exception_on_invalid_schema(
        GenericEvent $event,
        ParameterBag $propertiesBag,
        CategoryPropertyRepository $repository,
        EntityManager $entityManager,
        SchemaValidator $validator,
        CategoryInterface $category1,
        CategoryInterface $category2
    ): void {
        $event->getSubject()->willReturn([$category1, $category2]);
        $category1->getCode()->willReturn('electronics');
        $category2->getCode()->willReturn('clothes');

        $propertiesBag->has('electronics')->willReturn(true);
        $propertiesBag->has('clothes')->willReturn(true);

        $propertiesBag->get('electronics')->willReturn(['foo' => []]);
        $propertiesBag->get('clothes')->willReturn(['faa' => []]);

        $validator->validate(['foo' => []])->willReturn(['error' => 'text']);
        $validator->validate(['faa' => []])->willReturn([]);

        $repository->findOrCreateByCategory(Argument::any())->shouldNotHaveBeenCalled();

        $entityManager->persist(Argument::any())->shouldNotHaveBeenCalled();

        $entityManager->flush()->shouldNotHaveBeenCalled();

        $this->shouldThrow(ValidationFailed::class)->during('onBulkCategoryPostSave', [$event]);
    }

    public function it_saves_with_existing_properties(
        GenericEvent $event,
        ParameterBag $propertiesBag,
        CategoryPropertyRepository $repository,
        EntityManagerInterface $entityManager,
        SchemaValidator $validator,
        CategoryInterface $category1,
        CategoryInterface $category2,
        CategoryProperty $categoryProperty1,
        CategoryProperty $categoryProperty2
    ): void {
        $event->getSubject()->willReturn([$category1, $category2]);
        $category1->getCode()->willReturn('electronics');
        $category2->getCode()->willReturn('clothes');

        $propertiesBag->has('electronics')->willReturn(true);
        $propertiesBag->has('clothes')->willReturn(true);

        $propertiesBag->get('electronics')->willReturn(['foo' => []]);
        $propertiesBag->get('clothes')->willReturn(['faa' => []]);

        $validator->validate(['foo' => []])->willReturn([]);
        $validator->validate(['faa' => []])->willReturn([]);

        $repository->findOrCreateByCategory($category1)->willReturn($categoryProperty1);
        $repository->findOrCreateByCategory($category2)->willReturn($categoryProperty2);

        $categoryProperty1->getProperties()->willReturn([]);
        $categoryProperty2->getProperties()->willReturn([]);

        $categoryProperty1->mergeProperties(['foo' => []])->shouldBeCalledOnce();
        $categoryProperty2->mergeProperties(['faa' => []])->shouldBeCalledOnce();

        $entityManager->persist($categoryProperty1)->shouldBeCalledOnce();
        $entityManager->persist($categoryProperty2)->shouldBeCalledOnce();

        $entityManager->flush()->shouldBeCalledTimes(2);

        $this->onBulkCategoryPostSave($event);
    }
}
