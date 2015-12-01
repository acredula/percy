<?php

namespace Percy\Repository;

use Percy\Dbal\DbalInterface;
use Percy\Entity\Collection;
use Percy\Entity\CollectionBuilderTrait;
use Percy\Entity\EntityInterface;
use Percy\Http\QueryStringParserTrait;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

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
     * @param array  $rules
     * @param string $start
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
     * {@inheritdoc}
     */
    public function attachRelationships(Collection $collection, array $relationships = [])
    {
        foreach ($collection->getIterator() as $entity) {
            // @todo sort filtering of requested relationships
            array_walk($entity->getRelationshipKeys(), [$this, 'attachEntityRelationships'], $entity);
        }

        return $collection;
    }

    /**
     * Attach relationships to a specific entity.
     *
     * @param string                        $entityType
     * @param string                        $relationship
     * @param \Percy\Entity\EntityInterface $entity
     *
     * @throws \RuntimeException when relationship has not been properly defined
     *
     * @return void
     */
    protected function attachEntityRelationships($entityType, $relationship, EntityInterface $entity)
    {
        if (! array_key_exists($relationship, $this->getRelationshipMap())) {
            throw new RuntimeException(
                sprintf('(%s) is not defined in the (%s) relationship map', $relationship, get_class($this))
            );
        }

        $map = $this->getRelationshipMap()[$relationship];

        // @todo integrity check on structure of relationship map

        $query = sprintf(
            'SELECT * FROM %s LEFT JOIN %s ON %s.%s = %s.%s WHERE %s = :%s',
            $map['defined_in']['table'],
            $map['target']['table'],
            $map['target']['table'],
            $map['target']['primary'],
            $map['defined_in']['table'],
            $map['target']['relationship'],
            $map['defined_in']['primary'],
            $map['defined_in']['entity']
        );

        $result = $this->dbal->execute($query, [
            $map['defined_in']['entity'] => $entity[$map['defined_in']['entity']]
        ]);

        $entity[$relationship] = $this->buildCollection($result, $entityType);
    }

    /**
     * Get possible relationships and the properties attached to them.
     *
     * @return array
     */
    abstract protected function getRelationshipMap();

    /**
     * Returns table that repository is reading from.
     *
     * @return string
     */
    abstract protected function getTable();
}
