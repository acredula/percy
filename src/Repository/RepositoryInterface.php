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
     * @param string                                         $field
     * @param mixed                                          $value
     * @param \Psr\Http\Message\ServerRequestInterface|null  $value
     *
     * @return integer
     */
    public function countByField($field, $value, ServerRequestInterface $request = null);

    /**
     * Get one or many resources by field name => values.
     *
     * @param string                                         $field
     * @param mixed                                          $value
     * @param \Psr\Http\Message\ServerRequestInterface|null  $value
     *
     * @return \Percy\Entity\Collection
     */
    public function getByField($field, $value, ServerRequestInterface $request = null);

    /**
     * Attach relationships to a collection.
     *
     * @param \Percy\Entity\Collection                      $collection
     * @param array|null                                    $include
     * @param \Psr\Http\Message\ServerRequestInterface|null $request
     *
     * @return \Percy\Entity\Collection
     */
    public function attachRelationships(
        Collection $collection,
        $include                        = null,
        ServerRequestInterface $request = null
    );

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
