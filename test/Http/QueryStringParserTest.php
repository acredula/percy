<?php

namespace Acredula\DataMapper\Test;

use Acredula\DataMapper\Test\Asset\QueryStringParserTraitStub;

class QueryStringParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the sanitiser maps an empty query string.
     */
    public function testSanitiserMapsEmptyQuery()
    {
        $sanitiser = new QueryStringParserTraitStub;

        $query = $sanitiser->parseQueryString('');

        $this->assertSame([], $query);
    }

    /**
     * Asserts that the sanitiser ignores arguments not in whitelist.
     */
    public function testSanitiserIgnoresArgumentsNotInWhitelist()
    {
        $sanitiser = new QueryStringParserTraitStub;

        $query = $sanitiser->parseQueryString('something=something');

        $this->assertSame([], $query);
    }

    /**
     * Asserts that the sanitiser maps a single filter.
     */
    public function testSanitiserMapsSingleFilter()
    {
        $sanitiser = new QueryStringParserTraitStub;

        $query = $sanitiser->parseQueryString('sort=something&filter=field|=|value');

        $this->assertSame([
            'sort'   => 'something',
            'filter' => [
                [
                    'field'     => 'field',
                    'delimiter' => '=',
                    'value'     => 'value'
                ]
            ]
        ], $query);
    }

    /**
     * Asserts that the sanitiser maps a multiple filters.
     */
    public function testSanitiserMapsMultipleFilters()
    {
        $sanitiser = new QueryStringParserTraitStub;

        $query = $sanitiser->parseQueryString('sort=something&filter[]=field|=|value&filter[]=field2|<|value2');

        $this->assertSame([
            'sort'   => 'something',
            'filter' => [
                [
                    'field'     => 'field',
                    'delimiter' => '=',
                    'value'     => 'value'
                ],
                [
                    'field'     => 'field2',
                    'delimiter' => '<',
                    'value'     => 'value2'
                ]
            ]
        ], $query);
    }

    /**
     * Asserts that the sanitiser throws an exception when filter is malformed.
     */
    public function testMalformedFilterThrowsException()
    {
        $this->setExpectedException('InvalidArgumentException');

        $sanitiser = new QueryStringParserTraitStub;

        $query = $sanitiser->parseQueryString('filter=field|value');
    }

    /**
     * Asserts that the sanitiser throws an exception when attempting to use an invalid delimiter.
     */
    public function testInvalidDelimiterThrowsException()
    {
        $this->setExpectedException('InvalidArgumentException', '(has) is not an accepted delimiter');

        $sanitiser = new QueryStringParserTraitStub;

        $query = $sanitiser->parseQueryString('filter=field|has|value');
    }
}
