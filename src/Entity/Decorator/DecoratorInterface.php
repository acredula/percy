<?php

namespace Acredula\DataMapper\Entity\Decorator;

use Acredula\DataMapper\Entity\EntityInterface;

interface DecoratorInterface
{
    /**
     * Decorate an entity.
     *
     * @param \Acredula\DataMapper\Entity\EntityInterface $entity
     *
     * @return void
     */
    public function decorate(EntityInterface $entity);
}
