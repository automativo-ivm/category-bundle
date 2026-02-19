<?php

declare(strict_types=1);

namespace Flagbit\Bundle\CategoryBundle\Repository;

use Akeneo\Category\Infrastructure\Component\Model\CategoryInterface;
use Doctrine\ORM\EntityRepository;
use Flagbit\Bundle\CategoryBundle\Entity\CategoryProperty;

use function assert;

class CategoryPropertyRepository extends EntityRepository
{
    public function findByCategory(CategoryInterface $category): ?CategoryProperty
    {
        $categoryProperty = $this->findOneBy(['category' => $category]);

        assert($categoryProperty instanceof CategoryProperty || $categoryProperty === null);

        return $categoryProperty;
    }

    public function findOrCreateByCategory(CategoryInterface $category): CategoryProperty
    {
        /** @phpstan-var CategoryProperty|null $categoryProperty */
        $categoryProperty = $this->findByCategory($category);
        if ($categoryProperty === null) {
            $categoryProperty = new CategoryProperty($category);
        }

        return $categoryProperty;
    }
}
