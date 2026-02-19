<?php

declare(strict_types=1);

namespace Flagbit\Bundle\CategoryBundle\Tests\OverriddenServices;

use Akeneo\Category\Infrastructure\Component\Model\Category;
use Flagbit\Bundle\CategoryBundle\Entity\CategoryConfig;
use Flagbit\Bundle\CategoryBundle\Entity\CategoryProperty;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class SerializerTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testCategoryPropertyNormalizer(): void
    {
        $serializer = self::$container->get('pim_internal_api_serializer');

        $context = [AbstractNormalizer::IGNORED_ATTRIBUTES => ['category']];

        $entity = new CategoryProperty(new Category());
        $entity->setProperties(['foo' => ['bar' => 'baz']]);

        self::assertSame($serializer->normalize($entity, 'internal_api', $context), ['properties' => ['foo' => ['bar' => 'baz']]]);
    }

    public function testCategoryConfigNormalizer(): void
    {
        $serializer = self::$container->get('pim_internal_api_serializer');

        $entity = new CategoryConfig(['foo' => ['bar' => 'baz']]);

        self::assertSame($serializer->normalize($entity, 'internal_api'), ['config' => ['foo' => ['bar' => 'baz']]]);
    }
}
