<?php

namespace Percy\Test\Repository;

use Percy\Test\Asset\SqlRepositoryStub;

class SqlRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the repository can build a request, execute it and return a collection.
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

}
