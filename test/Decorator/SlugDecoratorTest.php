<?php

namespace Percy\Test\Decorator;

use Percy\Decorator\SlugDecorator;
use Percy\Entity\EntityInterface;

class SlugDecoratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the decorator can create a slug.
     */
    public function testDecoratorCreatesSlug()
    {
        $entity = $this->getMock(EntityInterface::class);

        $entity->expects($this->at(0))->method('offsetGet')->with($this->equalTo('forename'))->will($this->returnValue('Phil Ronald'));
        $entity->expects($this->at(1))->method('offsetGet')->with($this->equalTo('surname'))->will($this->returnValue('Bennett'));

        $entity->expects($this->at(2))->method('offsetSet')->with($this->equalTo('slug'), $this->equalTo('phil-ronald-bennett'));

        $decorator = new SlugDecorator;

        $decorator($entity, ['forename', 'surname']);
    }
}
