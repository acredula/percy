<?php

namespace Percy\Test\Asset;

use Percy\Test\Asset\EntityStub;

class SqlRepositoryStub extends BaseSqlRepositoryStub
{
    protected $relationships = [
        'some_relationship' => [
            'defined_in' => [
                'table'   => 'some_table',
                'primary' => 'some_uuid',
                'entity'  => 'uuid'
            ],
            'target' => [
                'table'        => 'another_table',
                'relationship' => 'another_uuid',
                'primary'      => 'uuid'
            ]
        ]
    ];
}
