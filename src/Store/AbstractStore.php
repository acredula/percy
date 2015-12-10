<?php

namespace Percy\Store;

use InvalidArgumentException;
use Percy\Decorator\DecoratorInterface;
use Percy\Entity\Collection;
use Percy\Entity\EntityInterface;

abstract class AbstractStore implements StoreInterface
{
    /**
     * Iterate collection and apply decorators based on action.
     *
     * @param \Percy\Entity\Collection $collection
     * @param integer                  $action
     *
     * @return boolean
     */
    protected function decorate(Collection $collection, $action)
    {
        foreach ($collection->getIterator() as $entity) {
            array_walk($entity->getDecorators($type), [$this, 'invokeDecorators'], [$entity]);
        }
    }

    /**
     * Invoke callable decorator on entity.
     *
     * @param string                        $decorator
     * @param array                         $properties
     * @param \Percy\Entity\EntityInterface $entity
     *
     * @return void
     */
    protected function invokeDecorators($decorator, array $properties, EntityInterface $entity)
    {
        $decorator = new $decorator;

        if (! $decorator instanceof DecoratorInterface) {
            throw new InvalidArgumentException(
                sprintf('(%s) must be an instance of (Percy\Decorator\DecoratorInterface)', get_class($decorator))
            );
        }

        $decorator($entity, $properties);
    }

    /**
     * Iterate collection and validate data.
     *
     * @param \Percy\EntityCollection $collection
     *
     * @return boolean
     */
    public function validate(Collection $collection)
    {

    }
}
