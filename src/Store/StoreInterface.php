<?php

namespace Percy\Store;

use Percy\Entity\Collection;

interface StoreInterface
{
    const ON_CREATE = 0;
    const ON_READ   = 1;
    const ON_UPDATE = 2;
    const ON_DELETE = 3;

    /**
     * Logic to achieve what the store needs to do when creating a record in the database.
     *
     * @param  \Percy\Entity\Collection $collection
     * @return boolean
     */
    public function create(Collection $collection);

    /**
     * Logic to achieve what the store needs to do when reading a record from the database.
     *
     * @param  \Percy\Entity\Collection $collection
     * @return boolean
     */
    public function read(Collection $collection);

    /**
     * Logic to achieve what the store needs to do when updating a record in the database.
     *
     * @param  \Percy\Entity\Collection $collection
     * @return boolean
     */
    public function update(Collection $collection);

    /**
     * Logic to achieve what the store needs to do when deleting a record from the database.
     *
     * @param  \Percy\Entity\Collection $collection
     * @return boolean
     */
    public function delete(Collection $collection);
}
