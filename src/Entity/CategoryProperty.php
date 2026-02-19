<?php

declare(strict_types=1);

namespace Flagbit\Bundle\CategoryBundle\Entity;

use Akeneo\Category\Infrastructure\Component\Model\CategoryInterface;

class CategoryProperty
{
    private ?int $id = null;

    /** @var mixed[] */
    private array $properties = [];

    private CategoryInterface $category;

    public function __construct(CategoryInterface $category)
    {
        $this->category = $category;
    }

    /**
     * @return mixed[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Merge original data with new data to prevent dropping existing property data.
     *
     * The passed new data will be written into the {@see CategoryProperty::$properties} array.
     * Using this function ensures that existing properties won't be dropped, like it would happen
     * if just {@see CategoryProperty::getProperties()} is used.
     *
     * @phpstan-param array<string, mixed> $newData
     */
    public function mergeProperties(array $newData): void
    {
        $mergedArray = $this->properties;

        foreach ($newData as $key => $value) {
            $mergedArray[$key] = $value;
        }

        $this->properties = $mergedArray;
    }

    /**
     * @param mixed[] $properties
     */
    public function setProperties(array $properties): void
    {
        $this->properties = $properties;
    }

    public function getCategory(): CategoryInterface
    {
        return $this->category;
    }
}
