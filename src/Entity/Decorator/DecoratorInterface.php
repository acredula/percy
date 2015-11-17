<?php

namespace Percy\Entity\Decorator;

use Percy\Entity\EntityInterface;

interface DecoratorInterface
{
    /**
     * Decorate an entity.
     *
     * @param \Percy\Entity\EntityInterface $entity
     *
     * @return void
     */
    public function decorate(EntityInterface $entity);
}
