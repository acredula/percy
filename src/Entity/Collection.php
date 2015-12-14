<?php

namespace Percy\Entity;

use Countable;
use IteratorAggregate;

class Collection implements IteratorAggregate, Countable
{
    /**
     * @var \Percy\Entity\EntityInterface[]
     */
    protected $entities = [];

    /**
     * @var integer
     */
    protected $total = 0;

    /**
     * Return indexed array of array representations of entities.
     *
     * @param array $scopes
     *
     * @return array
     */
    public function toArray(array $scopes = [])
    {
        $collection = [];

        foreach ($this->getIterator() as $entity) {
            $collection[] = $entity->toArray($scopes);
        }

        return $collection;
    }

    /**
     * Adds an entity to the collection.
     *
     * @param \Percy\Entity\EntityInterface $entity
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
        $count = count($this);

        for ($i = 0; $i < $count; $i++) {
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

    /**
     * Set the total number of results relating to the collection.
     *
     * @param integer $total
     *
     * @return self
     */
    public function setTotal($total)
    {
        $this->total = $total;

        return $this;
    }

    /**
     * Get the total number of results relating to the collection.
     *
     * @return integer
     */
    public function getTotal()
    {
        return ($this->total === 0) ? count($this) : $this->total;
    }
}
