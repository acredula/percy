<?php

namespace Percy\Test\Asset;

use Percy\Test\Asset\EntityStub;

class SqlRepositoryPartialMapStub extends BaseSqlRepositoryStub
{
    protected $relationships = [
        'some_relationship' => [
            'defined_in' => [
            ],
            'target' => [
            ]
        ]
    ];
}
