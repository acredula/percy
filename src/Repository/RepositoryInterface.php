<?php

namespace Percy\Repository;

use Psr\Http\Message\ServerRequestInterface;

interface RepositoryInterface
{
    /**
     * Get resources based on parameters attached to the request object.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param integer                                  $count
     *
     * @return \Percy\Entity\Collection|integer
     */
    public function getFromRequest(ServerRequestInterface $request, $count = false);

    /**
     * Get one or many resources by field name => values.
     *
     * @param string  $field
     * @param mixed   $value
     * @param integer $count
     *
     * @return \Percy\Entity\Collection|integer
     */
    public function getByField($field, $value, $count = false);

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
