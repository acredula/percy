<?php

namespace Percy\Test\Asset;

use Percy\Entity\AbstractEntity;
use Percy\Entity\EntityInterface;

class EntityStub extends AbstractEntity implements EntityInterface
{
    protected $mapping = [
        'uuid'       => [],
        'some_field' => [
            'validation' => 'rules',
            'type'       => 'string'
        ],
        'another_field' => []
    ];

    protected $relationships = ['some_relationship' => 'Percy\Test\Asset\EntityStub'];
}
