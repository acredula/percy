<?php

namespace Percy\Decorator\Date;

use DateTime;

class TimestampDecorator implements DecoratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(EntityInterface $entity, array $properties = [])
    {
        foreach ($properties as $property) {
            if (isset($entity[$property])) {
                $entity[$property] = (new DateTime($entity[$property]))->format('U');
            }
        }
    }
}
