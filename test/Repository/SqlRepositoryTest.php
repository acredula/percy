<?php

namespace Percy\Test\Repository;

use Aura\Sql\ExtendedPdoInterface;
use Percy\Entity\Collection;
use Percy\Test\Asset\EntityStub;
use Percy\Test\Asset\SqlRepositoryIncompleteMapStub;
use Percy\Test\Asset\SqlRepositoryNoMapStub;
use Percy\Test\Asset\SqlRepositoryPartialMapStub;
use Percy\Test\Asset\SqlRepositoryStub;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class SqlRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    protected $queryToQuery = [
        [
            'query_string' => 'sort=some_table.something|desc&filter[]=some_field|=|value1&filter[]=another_field|<>|value2&limit=50&offset=0',
            'data_query'   => 'SELECT * FROM some_table WHERE some_field = :some_field_0 AND another_field <> :another_field_1 ORDER BY some_table.something DESC LIMIT 0,50',
            'count_query'  => 'SELECT *, COUNT(*) as total FROM some_table WHERE some_field = :some_field_0 AND another_field <> :another_field_1',
            'binds'        => ['some_field_0' => 'value1', 'another_field_1' => 'value2']
        ],
        [
            'query_string' => 'search=some_field|term&minscore=1.0',
            'data_query'   => 'SELECT * FROM some_table WHERE MATCH (some_field) AGAINST (:match_bind IN BOOLEAN MODE) HAVING MATCH (some_field) AGAINST (:match_bind) > :score_bind ORDER BY MATCH (some_field) AGAINST (:match_bind) > :score_bind',
            'count_query'  => 'SELECT *, COUNT(*) as total FROM some_table WHERE MATCH (some_field) AGAINST (:match_bind IN BOOLEAN MODE) HAVING MATCH (some_field) AGAINST (:match_bind) > :score_bind',
            'binds'        => ['match_bind' => 'term', 'score_bind' => 1.0]
        ],
        [
            'query_string' => 'sort=rand',
            'data_query'   => 'SELECT * FROM some_table ORDER BY RAND()',
            'count_query'  => 'SELECT *, COUNT(*) as total FROM some_table',
            'binds'        => []
        ],
        [
            'query_string' => 'sort=ignored_table.col|asc',
            'data_query'   => 'SELECT * FROM some_table',
            'count_query'  => 'SELECT *, COUNT(*) as total FROM some_table',
            'binds'        => []
        ]
    ];

    /**
     * Asserts that the repository can build and execute a query by field.
     */
    public function testSqlRepoBuildsQueriesFromRequestAndReturnsCollection()
    {
        foreach ($this->queryToQuery as $query) {
            $this->getByRequestAssertions($query['query_string'], $query['data_query'], $query['count_query'], $query['binds']);
        }
    }

    /**
     * Checks assertions on a get by request.
     *
     * @param string $queryString
     * @param string $dataQuery
     * @param string $countQuery
     * @param array  $binds
     *
     * @return void
     */
    protected function getByRequestAssertions($queryString, $dataQuery, $countQuery, array $binds)
    {
        $uri     = $this->getMock(UriInterface::class);
        $request = $this->getMock(ServerRequestInterface::class);
        $dbal    = $this->getMock(ExtendedPdoInterface::class);

        $uri->expects($this->exactly(2))->method('getQuery')->will($this->returnValue($queryString));

        $request->expects($this->exactly(2))->method('getUri')->will($this->returnValue($uri));

        $dbal->expects($this->at(0))->method('fetchAll')->with($this->equalTo($dataQuery), $this->equalTo($binds))->will($this->returnValue([[], []]));

        $dbal->expects($this->at(1))->method('fetchOne')->with($this->equalTo($countQuery), $this->equalTo($binds))->will($this->returnValue(['total' => 10]));

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
        $dbal = $this->getMock(ExtendedPdoInterface::class);

        $dbal->expects($this->at(0))->method('fetchAll')->with(
            $this->equalTo('SELECT * FROM some_table WHERE some_table.field IN (:field)'),
            $this->equalTo(['field' => 'value1,value2'])
        )->will(
            $this->returnValue([[], []])
        );

        $dbal->expects($this->at(1))->method('fetchOne')->with(
            $this->equalTo('SELECT COUNT(*) as total FROM some_table WHERE some_table.field IN (:field)'),
            $this->equalTo(['field' => 'value1,value2'])
        )->will(
            $this->returnValue(['total' => 10])
        );

        $collection = (new SqlRepositoryStub($dbal))->getByField('field', ['value1', 'value2']);

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(2, $collection);
        $this->assertSame(10, $collection->getTotal());
    }
}
