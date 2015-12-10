<?php

namespace Percy\Decorator;

use Percy\Entity\EntityInterface;

interface DecoratorInterface
{
    /**
     * Decorate the entity.
     *
     * @param \Percy\Entity\EntityInterface $entity
     * @param array                         $properties Properties to decorate
     *
     * @return void
     */
    public function __invoke(EntityInterface $entity, array $properties = []);
}
