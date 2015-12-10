<?php

namespace Percy\Decorator;

interface DecoratorInterface
{
    /**
     * Decorate the entity.
     *
     * @param \Percy\Entity\EntityInterface $entity
     * @param array                         $properties Properties to decorate, empty if unused
     *
     * @return void
     */
    public function __invoke(EntityInterface $entity, array $properties = []);
}
