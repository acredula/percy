<?php

namespace Percy\Store;

use Aura\Filter\Exception\FilterFailed;
use Aura\Filter\FilterFactory;
use InvalidArgumentException;
use Percy\Decorator\DecoratorTrait;
use Percy\Entity\Collection;
use Percy\Exception\ValidationException;

abstract class AbstractStore implements StoreInterface
{
    use DecoratorTrait;

    /**
     * @var \Aura\Filter\FilterFactory
     */
    protected $filter;

    /**
     * Construct.
     *
     * @param \Aura\Filter\FilterFactory
     */
    public function __construct(FilterFactory $filter)
    {
        $this->filter = $filter;
    }

    /**
     * Iterate collection and validate data.
     *
     * @param \Percy\Entity\Collection $collection
     *
     * @throws \Percy\Exception\ValidationException when first validation failure occurs
     *
     * @return boolean
     */
    public function validate(Collection $collection)
    {
        foreach ($collection->getIterator() as $entity) {
            if (is_null($entity->getValidator())) {
                continue;
            }

            $filter = $this->filter->newSubjectFilter($entity->getValidator());

            try {
                $data = $entity->getData([], false);

                foreach (array_keys($entity->getMapping()) as $property) {
                    $data[$property] = (array_key_exists($property, $data)) ? $data[$property] : null;
                }

                $filter($data);
            } catch (FilterFailed $e) {
                $exception = new ValidationException($e->getMessage());
                $exception->setFailures($e->getFailures()->getMessages());

                throw $exception;
            }
        }

        return true;
    }
}
