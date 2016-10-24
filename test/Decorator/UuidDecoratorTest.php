<?php

namespace Percy\Test\Decorator;

use Percy\Decorator\UuidDecorator;
use Percy\Entity\EntityInterface;

class UuidDecoratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the decorator can create a uuid.
     */
    public function testDecoratorCreatesUuid()
    {
        $entity = $this->createMock(EntityInterface::class);

        $entity->expects($this->once())->method('offsetSet')->with($this->equalTo('uuid'), $this->callback(function ($subject) {
            return (bool) preg_match('/^\{?[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}\}?$/i', $subject);
        }));

        $decorator = new UuidDecorator;

        $decorator($entity, ['uuid']);
    }
}
