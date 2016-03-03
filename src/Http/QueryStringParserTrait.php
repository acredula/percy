<?php

namespace Percy\Http;

use InvalidArgumentException;

trait QueryStringParserTrait
{
    /**
     * Parse HTTP query string and return array representation
     * to be attached to a database query.
     *
     * @param string $query
     *
     * @return array
     */
    public function parseQueryString($query)
    {
        if (empty($query)) {
            return [];
        }

        parse_str($query, $split);

        $query = [];

        while (list($key, $value) = each($split)) {
            $mapped = call_user_func_array([$this, 'filterQueryParams'], [$key, $value]);
            if ($mapped !== false) {
                $query[$key] = $mapped;
            }
        }

        return $query;
    }

    /**
     * Map the parsed query string in to correct array structure.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return array|boolean
     */
    protected function filterQueryParams($key, $value)
    {
        switch ($key) {
            case 'limit':
            case 'offset':
                return (int) $value;
            case 'sort':
                return $this->parseSort($value);
            case 'filter':
                return $this->parseFilters((array) $value);
            case 'search':
                return $this->parseSearch($value);
            case 'minscore':
                return (float) $value;
            default:
                return false;
        }
    }

    /**
     * Map sorts to a usable format.
     *
     * @param string $value
     *
     * @return array
     */
    protected function parseSort($value)
    {
        $map  = [];
        $sort = explode(',', $value);
    }

    /**
     * Map search to a usable format.
     *
     * @param string $value
     *
     * @return array
     */
    protected function parseSearch($value)
    {
        $search = explode('|', $value);

        if (count($search) !== 2) {
            throw new InvalidArgumentException(
                'Malformed query string, search format should be (search=field|term) or (search=field1,field2|term)'
            );
        }

        return [
            'fields' => $search[0],
            'term'   => $search[1]
        ];
    }

    /**
     * Map filters in to useable array.
     *
     * @param array $filters
     *
     * @return array
     */
    protected function parseFilters(array $filters)
    {
        $mapped = [];
        $param  = 0;

        foreach ($filters as $filter) {
            $filter = explode('|', $filter);

            if (count($filter) !== 3) {
                throw new InvalidArgumentException(
                    'Malformed query string, filter format should be (filter[]=field|delimiter|value)'
                );
            }

            $filter = array_combine(['field', 'delimiter', 'value'], $filter);

            $filter['binding']   = str_replace('.', '_', $filter['field']) . '_' . $param++;
            $filter['delimiter'] = strtolower($filter['delimiter']);
            $filter['delimiter'] = html_entity_decode($filter['delimiter']);

            if (! in_array($filter['delimiter'], [
                '=', '!=', '<>', '<=', '>=', '<', '>', 'in', 'not in', 'like', 'not like'
            ])) {
                throw new InvalidArgumentException(sprintf('(%s) is not an accepted delimiter', $filter['delimiter']));
            }

            $mapped[] = $filter;
        }

        return $mapped;
    }
}
