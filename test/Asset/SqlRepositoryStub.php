<?php

namespace Percy\Test\Asset;

use Percy\Repository\AbstractSqlRepository;
use Percy\Test\Asset\EntityStub;

class SqlRepositoryStub extends AbstractSqlRepository
{
    protected $relationships = [
        'author' => [
            'defined_in' => [
                'table'   => 'article',
                'primary' => 'article_uuid',
                'entity'  => 'uuid'
            ],
            'target' => [
                'table'        => 'user',
                'relationship' => 'user_uuid',
                'primary'      => 'uuid'
            ]
        ]
    ];

    public function getEntityType()
    {
        return EntityStub::class;
    }

    public function getTable()
    {
        return 'some_table';
    }
}
