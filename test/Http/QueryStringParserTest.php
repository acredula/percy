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

        $this->assertEquals(['filter' => []], $query);
    }

    /**
     * Asserts that the sanitiser maps a single filter.
     */
    public function testSanitiserMapsSingleFilter()
    {
        $sanitiser = new QueryStringParserTraitStub;

        $query = $sanitiser->parseQueryString('sort=something&filter=field|=|value');

        $this->assertEquals([
            'sort'   => 'something',
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

        $query = $sanitiser->parseQueryString('sort=something&filter[]=field|=|value&filter[]=field2|<|value2');

        $this->assertEquals([
            'sort'   => 'something',
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
     * Asserts that the sanitiser maps in filters.
     */
    public function testSanitiserMapsInFilters()
    {
        $sanitiser = new QueryStringParserTraitStub;

        $query = $sanitiser->parseQueryString('sort=something&filter[]=sauces|in|tomato,bbq');

        $this->assertEquals([
            'sort'   => 'something',
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

        $query = $sanitiser->parseQueryString('sort=something&filter[]=sauces|not in|tomato,bbq');

        $this->assertEquals([
            'sort'   => 'something',
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
