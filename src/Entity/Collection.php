<?php

namespace Acredula\DataMapper\Entity;

use Countable;
use IteratorAggregate;

class Collection implements IteratorAggregate, Countable
{
    /**
     * @var \Acredula\DataMapper\Entity\EntityInterface[]
     */
    protected $entities = [];

    /**
     * Return indexed array of array representations of entities.
     *
     * @return array
     */
    public function toArray()
    {
        $collection = [];

        foreach ($this->getIterator() as $entity) {
            $collection[] = $entity->toArray();
        }

        return $collection;
    }

    /**
     * Adds an entity to the collection.
     *
     * @param \Acredula\DataMapper\Entity\EntityInterface $entity
     *
     * @return self
     */
    public function addEntity(EntityInterface $entity)
    {
        $this->entities[] = $entity;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        for ($i = 0; $i < count($this); $i++) {
            yield $this->entities[$i];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->entities);
    }
}
