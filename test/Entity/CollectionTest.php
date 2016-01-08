<?php

namespace Percy\Test\Entity;

use Percy\Entity\Collection;

class CollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that a collection can add and iterate entities.
     */
    public function testCollectionAddsAndIteratesEntities()
    {
        $collection = new Collection;
        $arrayCollection = [];

        for ($i = 0; $i < 10; $i++) {
            $data = ['entity' => $i];
            $arrayCollection[] = $data;

            $entity = $this->getMock('Percy\Entity\EntityInterface');
            $entity->expects($this->exactly(2))->method('getData')->will($this->returnValue($data));

            $collection->addEntity($entity);

            $this->assertCount($i + 1, $collection);
        }

        $i = 0;

        foreach ($collection->getIterator() as $entity) {
            $this->assertSame($entity->getData(), [
                'entity' => $i
            ]);

            $i++;
        }

        $this->assertSame($arrayCollection, $collection->getData());
    }
}
