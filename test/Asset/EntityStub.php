<?php

namespace Percy\Test\Asset;

use Percy\Entity\EntityInterface;

class EntityStub implements EntityInterface
{
    public function toArray()
    {
        return [];
    }

    public function getMapping()
    {
        return [];
    }

    public function getValidationRules()
    {
        return [];
    }

    public function hydrate(array $array)
    {
        return $this;
    }

    public function offsetSet($key, $value) {}
    public function offsetGet($key) {}
    public function offsetExists($key) {}
    public function offsetUnset($key) {}
}
