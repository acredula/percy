<?php

namespace Percy\Test\Asset;

use Percy\Test\Asset\EntityStub;

class SqlRepositoryIncompleteMapStub extends BaseSqlRepositoryStub
{
    protected $relationships = [
        'some_relationship' => []
    ];
}
