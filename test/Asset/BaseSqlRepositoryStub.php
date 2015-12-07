<?php

namespace Percy\Test\Asset;

use Percy\Repository\AbstractSqlRepository;
use Percy\Test\Asset\EntityStub;

class BaseSqlRepositoryStub extends AbstractSqlRepository
{
    public function getEntityType()
    {
        return EntityStub::class;
    }

    public function getTable()
    {
        return 'some_table';
    }
}
