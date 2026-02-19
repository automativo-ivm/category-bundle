<?php

declare(strict_types=1);

namespace spec\Flagbit\Bundle\CategoryBundle\Serializer\Normalizer;

use Akeneo\Category\Infrastructure\Component\Classification\Model\CategoryInterface;
use Flagbit\Bundle\CategoryBundle\Entity\CategoryConfig;
use Flagbit\Bundle\CategoryBundle\Entity\CategoryProperty;
use PhpSpec\ObjectBehavior;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ObjectNormalizerSpec extends ObjectBehavior
{
    public function let(NormalizerInterface $objectNormlizer): void
    {
        $this->beConstructedWith($objectNormlizer);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(NormalizerInterface::class);
    }

    public function it_only_normalizes_this_bundles_entities(
        CategoryProperty $categoryProperty,
        CategoryConfig $categoryConfig,
        CategoryInterface $category
    ): void {
        $this->supportsNormalization($categoryProperty)->shouldReturn(true);
        $this->supportsNormalization($categoryConfig)->shouldReturn(true);
        $this->supportsNormalization($category)->shouldReturn(false);
    }

    public function it_passes_object_to_normalizer(
        CategoryProperty $categoryProperty,
        NormalizerInterface $objectNormlizer
    ): void {
        $objectNormlizer->normalize($categoryProperty, null, [])->willReturn([]);

        $this->normalize($categoryProperty)->shouldReturn([]);
    }
}
