<?php

namespace Percy\Test\Repository;

use Percy\Entity\Collection;
use Percy\Test\Asset\EntityStub;
use Percy\Test\Asset\SqlRepositoryIncompleteMapStub;
use Percy\Test\Asset\SqlRepositoryNoMapStub;
use Percy\Test\Asset\SqlRepositoryPartialMapStub;
use Percy\Test\Asset\SqlRepositoryStub;

class SqlRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the repository can build a query, execute it and return a collection.
     */
    public function testSqlRepoBuildsQueryFromRequestAndReturnsCollection()
    {
        $uri     = $this->getMock('Psr\Http\Message\UriInterface');
        $request = $this->getMock('Psr\Http\Message\ServerRequestInterface');
        $dbal    = $this->getMock('Percy\Dbal\DbalInterface');

        $uri->expects($this->exactly(2))->method('getQuery')->will($this->returnValue(
            'sort=something&sort_direction=desc&filter[]=field1|=|value1&filter[]=field2|<>|value2&limit=50&offset=0'
        ));

        $request->expects($this->exactly(2))->method('getUri')->will($this->returnValue($uri));

        $dbal->expects($this->at(0))->method('execute')->with(
            $this->equalTo('SELECT * FROM some_table WHERE field1 = :field1 AND field2 <> :field2 ORDER BY something DESC LIMIT 0,50'),
            $this->equalTo(['field1' => 'value1', 'field2' => 'value2'])
        )->will(
            $this->returnValue([[], []])
        );

        $dbal->expects($this->at(1))->method('execute')->with(
            $this->equalTo('SELECT COUNT(*) as total FROM some_table WHERE field1 = :field1 AND field2 <> :field2'),
            $this->equalTo(['field1' => 'value1', 'field2' => 'value2'])
        )->will(
            $this->returnValue(['total' => 10])
        );

        $collection = (new SqlRepositoryStub($dbal))->getFromRequest($request);

        $this->assertInstanceOf('Percy\Entity\Collection', $collection);
        $this->assertCount(2, $collection);
        $this->assertSame(10, $collection->getTotal());
    }

    /**
     * Asserts that the repository can build and execute a query by field.
     */
    public function testSqlRepoBuildsQueryFromFieldAndReturnsCollection()
    {
        $dbal = $this->getMock('Percy\Dbal\DbalInterface');

        $dbal->expects($this->at(0))->method('execute')->with(
            $this->equalTo('SELECT * FROM some_table WHERE field IN (:field)'),
            $this->equalTo(['field' => 'value1,value2'])
        )->will(
            $this->returnValue([[], []])
        );

        $dbal->expects($this->at(1))->method('execute')->with(
            $this->equalTo('SELECT COUNT(*) as total FROM some_table WHERE field IN (:field)'),
            $this->equalTo(['field' => 'value1,value2'])
        )->will(
            $this->returnValue(['total' => 10])
        );

        $collection = (new SqlRepositoryStub($dbal))->getByField('field', ['value1', 'value2']);

        $this->assertInstanceOf('Percy\Entity\Collection', $collection);
        $this->assertCount(2, $collection);
        $this->assertSame(10, $collection->getTotal());
    }

    /**
     * Asserts that an exception is thrown when no relationship mapping is defined.
     */
    public function testRepositoryThrowsExceptionWhenNoRelationshipMapping()
    {
        $dbal = $this->getMock('Percy\Dbal\DbalInterface');

        $this->setExpectedException('InvalidArgumentException', '(some_relationship) is not defined in the relationship map on (Percy\Test\Asset\SqlRepositoryNoMapStub)');
        $collection = new Collection;
        $collection->addEntity(new EntityStub);

        $repo = new SqlRepositoryNoMapStub($dbal);
        $repo->attachRelationships($collection);
    }

    /**
     * Asserts that an exception is thrown when relationships are mapped incorrectly.
     */
    public function testRepositoryThrowsExceptionWhenRelationshipsMappedIncorrectly()
    {
        $dbal = $this->getMock('Percy\Dbal\DbalInterface');

        $this->setExpectedException('RuntimeException');
        $collection = new Collection;
        $collection->addEntity(new EntityStub);

        $repo = new SqlRepositoryPartialMapStub($dbal);
        $repo->attachRelationships($collection);
    }

    /**
     * Asserts that an exception is thrown when relationships are incomplete.
     */
    public function testRepositoryThrowsExceptionWhenRelationshipsIncomplete()
    {
        $dbal = $this->getMock('Percy\Dbal\DbalInterface');

        $this->setExpectedException('RuntimeException');
        $collection = new Collection;
        $collection->addEntity(new EntityStub);

        $repo = new SqlRepositoryIncompleteMapStub($dbal);
        $repo->attachRelationships($collection);
    }

    /**
     * Asserts that the repository attaches relationships to an enity.
     */
    public function testRepositoryAttachesRelationships()
    {
        $dbal = $this->getMock('Percy\Dbal\DbalInterface');

        $dbal->expects($this->once())
             ->method('execute')
             ->with(
                 $this->equalTo('SELECT * FROM some_table LEFT JOIN another_table ON another_table.uuid = some_table.another_uuid WHERE some_uuid = :uuid'),
                 $this->equalTo(['uuid' => 'a-uuid'])
             )
             ->will($this->returnValue([]));

        $entity = (new EntityStub)->hydrate(['uuid' => 'a-uuid']);
        $collection = new Collection;
        $collection->addEntity($entity);

        $repo = new SqlRepositoryStub($dbal);

        $repo->attachRelationships($collection);

        $this->assertTrue(isset($entity['some_relationship']));
        $this->assertInstanceOf('Percy\Entity\Collection', $entity['some_relationship']);
        $this->assertCount(1, $collection);
    }
}
