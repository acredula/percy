<?php

namespace Acredula\DataMapper\Http;

use InvalidArgumentException;

trait QuerySanitiserTrait
{
    /**
     * Sanitise HTTP query string and return array representation
     * to be sent as a database query.
     *
     * @param string $query
     *
     * @return array
     */
    public function sanitiseQuery($query)
    {
        if (empty($query)) {
            return [];
        }

        parse_str($query, $split);

        $query = [];

        while (list($key, $value) = each($split)) {
            if ($mapped = call_user_func_array([$this, 'mapQuery'], [$key, $value])) {
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
    protected function mapQuery($key, $value)
    {
        switch ($key) {
            case 'limit':
            case 'offset':
            case 'sort_direction':
            case 'sort':
                return $value;
                break;
            case 'filter':
                return $this->mapFilters((array) $value);
                break;
            default:
                return false;
                break;
        }

        return false;
    }

    /**
     * Map filters in to useable array.
     *
     * @param array $filters
     *
     * @return array
     */
    protected function mapFilters(array $filters)
    {
        $mapped = [];

        foreach ($filters as $filter) {
            $filter = explode('|', $filter);

            if (count($filter) !== 3) {
                throw new InvalidArgumentException(
                    'Malformed query string, filter format should be (filter[]=field|delimiter|value)'
                );
            }

            $filter = array_combine(['field', 'delimiter', 'value'], $filter);

            $filter['delimiter'] = strtolower($filter['delimiter']);
            $filter['delimiter'] = html_entity_decode($filter['delimiter']);

            if (! in_array($filter['delimiter'], [
                '=', '!=', '<>', '<=', '>=', '<', '>', 'in', 'not in'
            ])) {
                throw new InvalidArgumentException(sprintf('(%s) is not an accepted delimiter', $filter['delimiter']));
            }

            $mapped[] = $filter;
        }

        return $mapped;
    }
}
