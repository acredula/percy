<?php

namespace Percy\Entity;

use ArrayAccess;
use Percy\Entity\Decorator\DecoratorInterface;

interface EntityInterface extends ArrayAccess
{
    /**
     * Return api representation of entity with _links.
     *
     * @param array $scopes
     *
     * @return array
     */
    public function toArray(array $scopes = []);

    /**
     * Return raw entity data filtered by scopes.
     *
     * @param array   $scopes
     * @param boolean $toPersist
     *
     * @return array
     */
    public function getData(array $scopes = [], $toPersist = true);

    /**
     * Return array mapping of the data for the resource.
     *
     * [
     *     'first_field_name'  => 'type',
     *     'second_field_name' => 'type'
     * ]
     *
     * @return array
     */
    public function getMapping();

    /**
     * Return array of relationships with their entity types.
     *
     * [
     *     'relationship_key' => 'Acme\Entity\SomeEntity'
     * ]
     *
     * @return array
     */
    public function getRelationships();

    /**
     * Return the decorators for a specific action.
     *
     * @param integer|null $action
     *
     * @return array
     */
    public function getDecorators($action = null);

    /**
     * Return name of validator class.
     *
     * @return string
     */
    public function getValidator();

    /**
     * Return the identifier of the entities target data source. e.g. mysql_table.
     *
     * @return string
     */
    public function getDataSource();

    /**
     * Return the primary property name for the entity.
     *
     * @return string
     */
    public function getPrimary();

    /**
     * Hydrate the entity with data.
     *
     * @param array $data
     *
     * @return self
     */
    public function hydrate(array $data);
}
