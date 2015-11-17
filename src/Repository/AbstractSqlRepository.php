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
    public function getFromRequest(ServerRequestInterface $request, $count = false)
    {
        $rules = $this->parseQueryString($request->getUri()->getQuery());

        // @todo figure out best way to pull explicit
        //       fields from the entity mapping
        $query  = sprintf(
            'SELECT %s FROM %s',
            ($count === true) ? 'COUNT(*) as total' : '*',
            $this->getTable()
        );

        $params = [];

        foreach ($rules['filter'] as $key => $where) {
            $keyword = ($key === 0) ? ' WHERE ' : ' AND ';
            $query  .= sprintf('%s %s %s :%s', $keyword, $where['field'], $where['delimiter'], $where['field']);

            $params[$where['field']] = $where['value'];
        }

        if (array_key_exists('sort', $rules)) {
            $query .= sprintf(' ORDER BY %s ', $rules['sort']);
            $query .= (array_key_exists('sort_direction', $rules)) ? $rules['sort_direction'] : 'ASC';
        }

        if ($count === true) {
            return $this->dbal->execute($query, $params)['total'];
        }

        if (array_key_exists('limit', $rules)) {
            $query .= ' LIMIT ';
            $query .= (array_key_exists('offset', $rules)) ? sprintf('%s,', $rules['offset']) : '';
            $query .= $rules['limit'];
        }

        return $this->buildCollection($this->dbal->execute($query, $params))
                    ->setTotal($this->getFromRequest($request, true));
    }

    /**
     * {@inheritdoc}
     */
    public function getByField($field, $value, $count = false)
    {
        $query  = sprintf(
            'SELECT %s FROM %s WHERE %s IN :%s',
            ($count === true) ? 'COUNT(*) as total' : '*',
            $this->getTable(),
            $field,
            $field
        );

        $params = [
            $field => implode(',', (array) $value)
        ];

        if ($count === true) {
            return $this->dbal->execute($query, $params)['total'];
        }

        return $this->buildCollection($this->dbal->execute($query, $params))
                    ->setTotal($this->getByField($field, $value, true));
    }

    /**
     * Returns table that repository is reading from.
     *
     * @return string
     */
    abstract protected function getTable();
}
