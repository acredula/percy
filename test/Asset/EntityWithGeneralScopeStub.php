<?php

namespace Percy\Test\Asset;

use Percy\Entity\AbstractEntity;
use Percy\Entity\EntityInterface;
use Percy\Store\StoreInterface;

class EntityWithGeneralScopeStub extends AbstractEntity implements EntityInterface
{
    protected $readScope  = 'read_scope';

    protected $writeScope = 'write_scope';

    protected $decorators = [];

    protected $mapping = [
        'field1' => ['read' => 'test.read'],
        'field2' => ['write' => 'test.write'],
        'field3' => ['persist' => false]
    ];

    protected $relationshipMap = [];

    protected $validator = 'Acme\Validator\EntityValidator';

    public function getPrimary()
    {
        return 'uuid';
    }

    public function getDataSource()
    {
        return 'some_table';
    }
}
