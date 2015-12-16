<?php

namespace Percy\Decorator\Date;

use DateTime;
use Percy\Decorator\DecoratorInterface;
use Percy\Entity\EntityInterface;

class NowTimestampDecorator implements DecoratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(EntityInterface $entity, array $properties = [])
    {
        foreach ($properties as $property) {
            $entity[$property] = (new DateTime)->format('U');
        }
    }
}
