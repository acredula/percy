<?php

namespace Percy\Store;

use InvalidArgumentException;
use Percy\Decorator\DecoratorTrait;
use Percy\Entity\Collection;

abstract class AbstractStore implements StoreInterface
{
    use DecoratorTrait;

    /**
     * Iterate collection and validate data.
     *
     * @param \Percy\EntityCollection $collection
     *
     * @return boolean
     */
    public function validate(Collection $collection)
    {

    }
}
