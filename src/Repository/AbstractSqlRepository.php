<?php

namespace Percy\Repository;

use Percy\Dbal\DbalInterface;
use Percy\Entity\CollectionBuilderTrait;
use Percy\Http\QueryStringParserTrait;
use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractSqlRepository implements RepositoryInterface
{
    use CollectionBuilderTrait;
    use QueryStringParserTrait;

    /**
     * @var \Percy\Dbal\DbalInterface
     */
    protected $dbal;

    /**
     * Construct.
     *
     * @param \Percy\Dbal\DbalInterface $dbal
     */
    public function __construct(DbalInterface $dbal)
    {
        $this->dbal = $dbal;
    }

    /**
     * {@inheritdoc}
     */
    public function countFromRequest(ServerRequestInterface $request)
    {
        $rules = $this->parseQueryString($request->getUri()->getQuery());
        list($query, $params) = $this->buildQueryFromRules($rules, 'SELECT COUNT(*) as total FROM ');

        return $this->dbal->execute($query, $params)['total'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFromRequest(ServerRequestInterface $request)
    {
        $rules = $this->parseQueryString($request->getUri()->getQuery());
        list($query, $params) = $this->buildQueryFromRules($rules);

        if (array_key_exists('sort', $rules)) {
            $query .= sprintf(' ORDER BY %s ', $rules['sort']);
            $query .= (array_key_exists('sort_direction', $rules)) ? $rules['sort_direction'] : 'ASC';
        }

        if (array_key_exists('limit', $rules)) {
            $query .= ' LIMIT ';
            $query .= (array_key_exists('offset', $rules)) ? sprintf('%d,', $rules['offset']) : '';
            $query .= $rules['limit'];
        }

        return $this->buildCollection($this->dbal->execute($query, $params))
                    ->setTotal($this->countFromRequest($request));
    }

    /**
     * Build a base query without sorting and limits from filter rules.
     *
     * @param  array  $rules
     * @param  string $start
     *
     * @return array
     */
    protected function buildQueryFromRules(array $rules, $start = 'SELECT * FROM ')
    {
        $query = $start . $this->getTable();

        $params = [];

        foreach ($rules['filter'] as $key => $where) {
            $keyword = ($key === 0) ? ' WHERE' : ' AND';
            $query  .= sprintf('%s %s %s :%s', $keyword, $where['field'], $where['delimiter'], $where['field']);

            $params[$where['field']] = $where['value'];
        }

        return [$query, $params];
    }

    /**
     * {@inheritdoc}
     */
    public function countByField($field, $value)
    {
        $query = sprintf('SELECT COUNT(*) as total FROM %s WHERE %s IN (:%s)', $this->getTable(), $field, $field);

        $params = [
            $field => implode(',', (array) $value)
        ];

        return $this->dbal->execute($query, $params)['total'];
    }

    /**
     * {@inheritdoc}
     */
    public function getByField($field, $value)
    {
        $query = sprintf('SELECT * FROM %s WHERE %s IN (:%s)', $this->getTable(), $field, $field);

        $params = [
            $field => implode(',', (array) $value)
        ];

        return $this->buildCollection($this->dbal->execute($query, $params))
                    ->setTotal($this->countByField($field, $value));
    }

    /**
     * Returns table that repository is reading from.
     *
     * @return string
     */
    abstract protected function getTable();
}
