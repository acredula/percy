<?php

namespace Percy\Test\Decorator;

use Percy\Decorator\Date\NowTimestampDecorator;
use Percy\Entity\EntityInterface;

class NowTimestampDecoratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the decorator can create a uuid.
     */
    public function testDecoratorCreatesUuid()
    {
        $entity = $this->createMock(EntityInterface::class);

        $entity->expects($this->once())->method('offsetSet')->with($this->equalTo('timestamp'), $this->callback(function ($subject) {
            return ($subject <= PHP_INT_MAX && $subject >= ~PHP_INT_MAX);
        }));

        $decorator = new NowTimestampDecorator;

        $decorator($entity, ['timestamp']);
    }
}
