<?php

declare(strict_types=1);

namespace Flagbit\Bundle\CategoryBundle\Connector\Processor\Normalization;

use Akeneo\Category\Infrastructure\Component\Model\CategoryInterface;
use Akeneo\Tool\Component\Batch\Item\InvalidItemException;
use Akeneo\Tool\Component\Batch\Item\ItemProcessorInterface;
use Akeneo\Tool\Component\Connector\Processor\Normalization\Processor;
use Flagbit\Bundle\CategoryBundle\Connector\ArrayConverter\StandardToFlat\CategoryProperty as StandardToFlatConverter;
use Flagbit\Bundle\CategoryBundle\Repository\CategoryPropertyRepository;

use function array_merge;

/**
 * Decorator to add category properties during normalization.
 *
 * This decorator class can be used to decorate Akeneo's processor service
 * used to normalize data for the category export. It finds and adds the category properties
 * provided by this bundle to the exported category's data.
 *
 * @see Processor
 */
class ProcessorDecorator implements ItemProcessorInterface
{
    protected CategoryPropertyRepository $categoryPropertyRepository;
    protected StandardToFlatConverter $standardToFlat;
    protected ItemProcessorInterface $baseProcessor;

    public function __construct(
        CategoryPropertyRepository $categoryPropertyRepository,
        StandardToFlatConverter $standardToFlat,
        ItemProcessorInterface $baseProcessor
    ) {
        $this->categoryPropertyRepository = $categoryPropertyRepository;
        $this->standardToFlat             = $standardToFlat;
        $this->baseProcessor              = $baseProcessor;
    }

    /**
     * @phpstan-param CategoryInterface $item
     *
     * @return mixed
     *
     * @throws InvalidItemException
     */
    public function process($item)
    {
        $categoryData = $this->baseProcessor->process($item);

        $categoryProperties = $this->categoryPropertyRepository->findByCategory($item);
        if ($categoryProperties !== null) {
            $categoryData = array_merge(
                $categoryData,
                $this->standardToFlat->convert($categoryProperties->getProperties())
            );
        }

        return $categoryData;
    }
}
