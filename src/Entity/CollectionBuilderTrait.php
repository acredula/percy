<?php

namespace Acredula\DataMapper\Entity;

trait CollectionBuilderTrait
{
    /**
     * Build a collection of entities from an indexed array of data.
     *
     * @param array $data
     *
     * @return \Acredula\DataMapper\Entity\Collection
     */
    public function buildCollection(array $data)
    {
        $collection = new Collection;

        foreach ($data as $row) {
            $entity = $this->getEntityType();

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
