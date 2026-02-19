<?php

declare(strict_types=1);

namespace spec\Flagbit\Bundle\CategoryBundle\Entity;

use Akeneo\Category\Infrastructure\Component\Model\CategoryInterface;
use Flagbit\Bundle\CategoryBundle\Entity\CategoryProperty;
use PhpSpec\ObjectBehavior;

class CategoryPropertySpec extends ObjectBehavior
{
    public function let(
        CategoryInterface $category
    ): void {
        $this->beConstructedWith($category);
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(CategoryProperty::class);
    }

    public function it_allow_to_aggregate_old_and_new_data(): void
    {
        $this->setProperties([
            'foo' => [
                'en_US' => [
                    'data' => 'new value',
                    'locale' => 'en_US',
                ],
                'fr_FR' => [
                    'data' => 'another value',
                    'locale' => 'fr_FR',
                ],
            ],
            'faa' => [
                'null' => [
                    'data' => 'more data',
                    'locale' => 'null',
                ],
            ],
        ]);

        $this->mergeProperties([
            'foo' => [
                'de_DE' => [
                    'data' => 'testen',
                    'locale' => 'de_DE',
                ],
                'en_US' => [
                    'data' => 'testing',
                    'locale' => 'en_US',
                ],
            ],
        ]);

        $this->getProperties()->shouldReturn([
            'foo' => [
                'de_DE' => [
                    'data' => 'testen',
                    'locale' => 'de_DE',
                ],
                'en_US' => [
                    'data' => 'testing',
                    'locale' => 'en_US',
                ],
            ],
            'faa' => [
                'null' => [
                    'data' => 'more data',
                    'locale' => 'null',
                ],
            ],
        ]);
    }
}
