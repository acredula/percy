<?php

namespace Acredula\DataMapper\Entity;

use Acredula\DataMapper\Entity\Decorator\DecoratorInterface;
use ArrayAccess;

interface EntityInterface extends ArrayAccess
{
    /**
     * Return array representation of resource data with any updates.
     *
     * @return array
     */
    public function getData();

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
     * Return array representation of resource data from the point it was hydrated.
     *
     * @return array
     */
    public function getOriginalData();

    /**
     * Hydrate the entity with data.
     *
     * @param array                                                    $data
     * @param \Acredula\DataMapper\Entity\Decorator\DecoratorInterface $decorators,...
     *
     * @return void
     */
    public function hydrate(array $data, DecoratorInterface ...$decorators);
}
