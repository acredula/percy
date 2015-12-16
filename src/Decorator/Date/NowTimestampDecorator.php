<?php

namespace Percy\Decorator\Date;

use DateTime;

class NowTimestampDecorator implements DecoratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(EntityInterface $entity, array $properties = [])
    {
        foreach ($properties as $property) {
            if (isset($entity[$property])) {
                continue;
            }

            $entity[$property] = (new DateTime)->format('U');
        }
    }
}
