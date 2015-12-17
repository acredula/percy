<?php

namespace Percy\Repository;

use Percy\Entity\Collection;
use Psr\Http\Message\ServerRequestInterface;

interface RepositoryInterface
{
    /**
     * Count resources based on parameters attached to the request object.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return integer
     */
    public function countFromRequest(ServerRequestInterface $request);

    /**
     * Get resources based on parameters attached to the request object.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Percy\Entity\Collection
     */
    public function getFromRequest(ServerRequestInterface $request);

    /**
     * Count resources by field name => values.
     *
     * @param string $field
     * @param mixed  $value
     *
     * @return integer
     */
    public function countByField($field, $value);

    /**
     * Get one or many resources by field name => values.
     *
     * @param string $field
     * @param mixed  $value
     *
     * @return \Percy\Entity\Collection
     */
    public function getByField($field, $value);

    /**
     * Build a collection of entities from an indexed array of data.
     *
     * @param array $data
     *
     * @return \Percy\Entity\Collection
     */
    public function buildCollection(array $data);

    /**
     * Attach the relationships needed for the collection.
     *
     * @param \Percy\Entity\Collection $collection
     * @param array                    $relationships
     * @param boolean                  $exclude whether $relationsips should be excluded or included
     *
     * @return \Percy\Entity\Collection
     */
    public function attachRelationships(Collection $collection, array $relationships = [], $exclude = false);

    /**
     * Get the primary entity type associated with the repository.
     *
     * @return string
     */
    public function getEntityType();
}
