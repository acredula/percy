<?php

namespace Acredula\DataMapper\Entity\Decorator;

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
