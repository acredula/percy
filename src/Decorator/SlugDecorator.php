<?php

namespace Percy\Decorator;

use Cocur\Slugify\Slugify;
use Percy\Entity\EntityInterface;

class SlugDecorator implements DecoratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function __invoke(EntityInterface $entity, array $properties = [])
    {
        $props = [];

        foreach ($properties as $property) {
            $props[] = $entity[$property];
        }

        $entity['slug'] = (new Slugify)->slugify(implode('-', $props));
    }
}
