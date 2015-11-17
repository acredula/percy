<?php

namespace Percy\Entity;

trait CollectionBuilderTrait
{
    /**
     * Build a collection of entities from an indexed array of data.
     *
     * @param array $data
     *
     * @return \Percy\Entity\Collection
     */
    public function buildCollection(array $data)
    {
        $collection = new Collection;

        foreach ($data as $row) {
            $entity = $this->getEntityType();
            $entity = new $entity;

            $collection->addEntity(
                (new $entity)->hydrate($row)
            );
        }

        return $collection;
    }

    /**
     * Get the primary entity type associated with the repository.
     *
     * @return string
     */
    abstract public function getEntityType();
}
