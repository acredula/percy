<?php

namespace Percy\Decorator;

use Percy\Entity\EntityInterface;
use Ramsey\Uuid\Uuid;

class UuidDecorator implements DecoratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(EntityInterface $entity, array $properties = [])
    {
        foreach ($properties as $property) {
            $entity[$property] = Uuid::uuid4()->toString();
        }
    }
}
