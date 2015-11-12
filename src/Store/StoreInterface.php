<?php

namespace Acredula\DataMapper\Store;

use Acredula\DataMapper\Entity\Collection;

interface StoreInterface
{
    /**
     * Logic to achieve what the store needs to do when creating a record in the database
     *
     * @param  \Acredula\DataMapper\Repository\Entity\Collection $collection
     * @return boolean
     */
    public function create(Collection $collection);

    /**
     * Logic to achieve what the store needs to do when reading a record from the database
     *
     * @param  \Acredula\DataMapper\Repository\Entity\Collection $collection
     * @return boolean
     */
    public function read(Collection $collection);

    /**
     * Logic to achieve what the store needs to do when updating a record in the database
     *
     * @param  \Acredula\DataMapper\Repository\Entity\Collection $collection
     * @return boolean
     */
    public function update(Collection $collection);

    /**
     * Logic to achieve what the store needs to do when deleting a record from the database
     *
     * @param  \Acredula\DataMapper\Repository\Entity\Collection $collection
     * @return boolean
     */
    public function delete(Collection $collection);
}
