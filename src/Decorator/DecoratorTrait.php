<?php

namespace Percy\Decorator;

use InvalidArgumentException;
use Percy\Entity\Collection;
use Percy\Entity\EntityInterface;

trait DecoratorTrait
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
            $decorators = $entity->getDecorators($action);
            array_walk($decorators, [$this, 'invokeDecorators'], $entity);
        }
    }

    /**
     * Invoke callable decorator on entity.
     *
     * @param array                         $properties
     * @param string                        $decorator
     * @param \Percy\Entity\EntityInterface $entity
     *
     * @return void
     */
    protected function invokeDecorators(array $properties, $decorator, EntityInterface $entity)
    {
        $decorator = new $decorator;

        if (! $decorator instanceof DecoratorInterface) {
            throw new InvalidArgumentException(
                sprintf('(%s) must be an instance of (Percy\Decorator\DecoratorInterface)', get_class($decorator))
            );
        }

        $decorator($entity, $properties);
    }
}
