<?php

namespace Percy\Test\Entity;

use Percy\Entity\Collection;
use Percy\Entity\EntityInterface;

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

            $entity = $this->getMock(EntityInterface::class);
            $entity->expects($this->any())->method('getDataSource')->will($this->returnValue('test'));
            $entity->expects($this->exactly(2))->method('getData')->will($this->returnValue($data));
            $entity->expects($this->exactly(1))->method('toArray')->will($this->returnValue($data));

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
        $this->assertSame([
            'count'     => 10,
            'total'     => 10,
            '_embedded' => ['test' => $arrayCollection]
        ], $collection->toArray());
    }
}
