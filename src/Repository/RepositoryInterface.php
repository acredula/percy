<?php

namespace Percy\Repository;

use Psr\Http\Message\ServerRequestInterface;

interface RepositoryInterface
{
    /**
     * Get resources based on parameters attached to the request object.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     *
     * @return \Percy\Entity\Collection
     */
    public function getFromRequest(ServerRequestInterface $request);

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
     * Get the primary entity type associated with the repository.
     *
     * @return string
     */
    public function getEntityType();
}
