<?php

namespace Acredula\DataMapper\Dbal;

interface DbalInterface
{
    /**
     * Execute and return results of a data store query.
     * All results (one or many) should be returned as an indexed array.
     *
     * @param string $query
     * @param array  $params
     *
     * @return array
     */
    public function execute($query, array $params = []);
}
