<?php

namespace Percy\Entity;

trait CollectionBuilderTrait
{
    /**
     * Build a collection of entities from an indexed array of data.
     *
     * @param array       $data
     * @param string|null $entityType
     *
     * @return \Percy\Entity\Collection
     */
    public function buildCollection(array $data, $entityType = null)
    {
        $collection = new Collection;

        foreach ($data as $row) {
            $entity = (is_null($entityType)) ? $this->getEntityType() : $entityType;
            $entity = new $entity;

            $collection->addEntity(
                $entity->hydrate($row)
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
