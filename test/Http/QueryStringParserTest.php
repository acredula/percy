<?php

namespace Percy\Test\Http;

use Percy\Test\Asset\QueryStringParserTraitStub;

class QueryStringParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the sanitiser maps an empty query string.
     */
    public function testSanitiserMapsEmptyQuery()
    {
        $sanitiser = new QueryStringParserTraitStub;

        $query = $sanitiser->parseQueryString('');

        $this->assertEquals([], $query);
    }

    /**
     * Asserts that the sanitiser ignores arguments not in whitelist.
     */
    public function testSanitiserIgnoresArgumentsNotInWhitelist()
    {
        $sanitiser = new QueryStringParserTraitStub;

        $query = $sanitiser->parseQueryString('something=something');

        $this->assertEquals([], $query);
    }

    /**
     * Asserts that the sanitiser maps a single filter.
     */
    public function testSanitiserMapsSingleFilter()
    {
        $sanitiser = new QueryStringParserTraitStub;

        $query = $sanitiser->parseQueryString('sort=table.something|desc&filter=field|=|value');

        $this->assertEquals([
            'sort'   => [
                [
                    'field'     => 'table.something',
                    'direction' => 'desc'
                ]
            ],
            'filter' => [
                [
                    'field'     => 'field',
                    'delimiter' => '=',
                    'value'     => 'value',
                    'binding'   => 'field_0'
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

        $query = $sanitiser->parseQueryString('sort=table.something|desc&filter[]=field|=|value&filter[]=field2|<|value2');

        $this->assertEquals([
            'sort'   => [
                [
                    'field'     => 'table.something',
                    'direction' => 'desc'
                ]
            ],
            'filter' => [
                [
                    'field'     => 'field',
                    'delimiter' => '=',
                    'value'     => 'value',
                    'binding'   => 'field_0'
                ],
                [
                    'field'     => 'field2',
                    'delimiter' => '<',
                    'value'     => 'value2',
                    'binding'   => 'field2_1'
                ]
            ]
        ], $query);
    }

    /**
     * Asserts that the sanitiser maps a random order sort.
     */
    public function testSanitiserMapsRandomSort()
    {
        $sanitiser = new QueryStringParserTraitStub;

        $query = $sanitiser->parseQueryString('sort=random');

        $this->assertEquals([
            'sort' => 'RAND()'
        ], $query);
    }

    /**
     * Asserts that the sanitiser maps search.
     */
    public function testSanitiserMapsSearch()
    {
        $sanitiser = new QueryStringParserTraitStub;

        $query = $sanitiser->parseQueryString('search=col|term&minscore=1.0');

        $this->assertEquals([
            'search' => [
                'fields' => 'col',
                'term'   => 'term'
            ],
            'minscore' => 1.0
        ], $query);
    }

    /**
     * Asserts that the sanitiser maps in filters.
     */
    public function testSanitiserMapsInFilters()
    {
        $sanitiser = new QueryStringParserTraitStub;

        $query = $sanitiser->parseQueryString('filter[]=sauces|in|tomato,bbq');

        $this->assertEquals([
            'filter' => [
                [
                    'field'     => 'sauces',
                    'delimiter' => 'in',
                    'value'     => 'tomato,bbq',
                    'binding'   => 'sauces_0'
                ]
            ]
        ], $query);
    }

    /**
     * Asserts that the sanitiser maps not in filters.
     */
    public function testSanitiserMapsNotInFilters()
    {
        $sanitiser = new QueryStringParserTraitStub;

        $query = $sanitiser->parseQueryString('filter[]=sauces|not in|tomato,bbq');

        $this->assertEquals([
            'filter' => [
                [
                    'field'     => 'sauces',
                    'delimiter' => 'not in',
                    'value'     => 'tomato,bbq',
                    'binding'   => 'sauces_0'
                ]
            ]
        ], $query);
    }

    /**
     * Asserts that the sanitiser maps like filters.
     */
    public function testSanitiserMapsLikeFilters()
    {
        $sanitiser = new QueryStringParserTraitStub;

        $query = $sanitiser->parseQueryString('filter[]=sauces|like|%sauce');

        $this->assertEquals([
            'filter' => [
                [
                    'field'     => 'sauces',
                    'delimiter' => 'like',
                    'value'     => '%sauce',
                    'binding'   => 'sauces_0'
                ]
            ]
        ], $query);
    }

    /**
     * Asserts that the sanitiser maps not like filters.
     */
    public function testSanitiserMapsNotLikeFilters()
    {
        $sanitiser = new QueryStringParserTraitStub;

        $query = $sanitiser->parseQueryString('filter[]=sauces|not like|soy%');

        $this->assertEquals([
            'filter' => [
                [
                    'field'     => 'sauces',
                    'delimiter' => 'not like',
                    'value'     => 'soy%',
                    'binding'   => 'sauces_0'
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

    /**
     * Asserts that the sanitiser throws an exception when incorrectly formatted search.
     */
    public function testInvalidSearchThrowsException()
    {
        $this->setExpectedException('InvalidArgumentException');

        $sanitiser = new QueryStringParserTraitStub;

        $query = $sanitiser->parseQueryString('search=field');
    }
}
