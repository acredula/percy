<?php

namespace Percy\Test\Asset;

use Percy\Entity\AbstractEntity;
use Percy\Entity\EntityInterface;
use Percy\Store\StoreInterface;

class EntityStub extends AbstractEntity implements EntityInterface
{
    protected $decorators = [
        StoreInterface::ON_CREATE => [
            'Acme\Decorator' => ['some_field']
        ]
    ];

    protected $mapping = [
        'uuid'       => [],
        'some_field' => [
            'type'       => 'string'
        ],
        'another_field' => []
    ];

    protected $relationships = ['some_relationship' => 'Percy\Test\Asset\EntityStub'];

    protected $validator = 'Acme\Validator\EntityValidator';
}
