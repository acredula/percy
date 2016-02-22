<?php

namespace Percy\Repository;

use Aura\Sql\ExtendedPdoInterface;
use InvalidArgumentException;
use Percy\Decorator\DecoratorTrait;
use Percy\Entity\Collection;
use Percy\Entity\CollectionBuilderTrait;
use Percy\Entity\EntityInterface;
use Percy\Http\QueryStringParserTrait;
use Percy\Store\StoreInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

abstract class AbstractSqlRepository implements RepositoryInterface
{
    use CollectionBuilderTrait;
    use DecoratorTrait;
    use QueryStringParserTrait;

    /**
     * @var \Aura\Sql\ExtendedPdoInterface
     */
    protected $dbal;

    /**
     *
     * @var mixed
     */
    protected $relationships = [];

    /**
     * Construct.
     *
     * @param \Aura\Sql\ExtendedPdoInterface $dbal
     */
    public function __construct(ExtendedPdoInterface $dbal)
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

        return (int) $this->dbal->fetchOne($query, $params)['total'];
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

        $query = trim(preg_replace('!\s+!', ' ', $query));

        $collection = $this->buildCollection($this->dbal->fetchAll($query, $params))
                           ->setTotal($this->countFromRequest($request));

        $this->decorate($collection, StoreInterface::ON_READ);

        return $collection;
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

        if (array_key_exists('filter', $rules)) {
            foreach ($rules['filter'] as $key => $where) {
                $keyword   = ($key === 0) ? ' WHERE' : ' AND';
                $delimiter = strtoupper($where['delimiter']);
                $binding   = (in_array($delimiter, ['IN', 'NOT IN'])) ? sprintf('(:%s)', $where['binding']) : ':' . $where['binding'];
                $query    .= sprintf('%s %s %s %s', $keyword, $where['field'], $delimiter, $binding);

                $params[$where['binding']] = $where['value'];
            }
        }

        return [$query, $params];
    }

    /**
     * {@inheritdoc}
     */
    public function countByField($field, $value, ServerRequestInterface $request = null)
    {
        $query = sprintf(
            "SELECT COUNT(*) as total FROM %s WHERE %s.%s IN (:%s)",
            $this->getTable(),
            $this->getTable(),
            $field,
            $field
        );

        $params = [
            $field => implode(',', (array) $value)
        ];

        return (int) $this->dbal->fetchOne($query, $params)['total'];
    }

    /**
     * {@inheritdoc}
     */
    public function getByField($field, $value, ServerRequestInterface $request = null)
    {
        $query = sprintf(
            'SELECT * FROM %s WHERE %s.%s IN (:%s)',
            $this->getTable(),
            $this->getTable(),
            $field,
            $field
        );

        // @todo - allow extra filtering from request

        $params = [
            $field => implode(',', (array) $value)
        ];

        $collection = $this->buildCollection($this->dbal->fetchAll($query, $params))
                           ->setTotal($this->countByField($field, $value));

        $this->decorate($collection, StoreInterface::ON_READ);

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function attachRelationships(
        Collection $collection,
        $include                        = null,
        ServerRequestInterface $request = null
    ) {
        foreach ($this->getRelationshipMap() as $key => $map) {
            if (is_array($include) && ! in_array($key, $include)) {
                continue;
            }

            $binds = $this->getRelationshipBinds($collection, $key, $map['defined_in']['entity']);

            if (empty($binds)) {
                continue;
            }

            $query = sprintf(
                'SELECT * FROM %s LEFT JOIN %s ON %s.%s = %s.%s WHERE %s.%s IN (%s)',
                $map['defined_in']['table'],
                $map['target']['table'],
                $map['target']['table'],
                $map['target']['primary'],
                $map['defined_in']['table'],
                $map['target']['relationship'],
                $map['defined_in']['table'],
                $map['defined_in']['primary'],
                implode(',', $binds)
            );

            // @todo - extend query with filters

            $result = $this->dbal->fetchAll($query, []);

            $this->attachRelationshipsToCollection($collection, $key, $result);
        }
    }

    /**
     * Iterate a result set and attach the relationship to it's correct entity
     * within a collection.
     *
     * @param \Percy\Entity\Collection $collection
     * @param string                   $relationship
     * @param array                    $data
     *
     * @return void
     */
    protected function attachRelationshipsToCollection(Collection $collection, $relationship, array $data)
    {
        $map           = $this->getRelationshipMap($relationship);
        $relationships = array_column($data, $map['defined_in']['primary']);

        $remove = [$map['defined_in']['primary'], $map['target']['relationship']];

        foreach ($data as &$resource) {
            $resource = array_filter($resource, function ($key) use ($remove) {
                return (! in_array($key, $remove));
            }, ARRAY_FILTER_USE_KEY);
        }

        foreach ($collection->getIterator() as $entity) {
            $entityRels = $entity->getRelationshipMap();

            if (! array_key_exists($relationship, $entityRels)) {
                continue;
            }

            $keys = array_keys(preg_grep("/{$entity[$map['defined_in']['entity']]}/", $relationships));
            $rels = array_filter($data, function ($key) use ($keys) {
                return in_array($key, $keys);
            }, ARRAY_FILTER_USE_KEY);

            $rels = $this->buildCollection($rels, $entityRels[$relationship])->setTotal(count($rels));
            $this->decorate($rels, StoreInterface::ON_READ);

            $entity->addRelationship($relationship, $rels);
        }
    }

    /**
     * Return relationship bind conditional.
     *
     * @param \Percy\Entity\Collection $collection
     * @param string                   $relationship
     * @param string                   $key
     *
     * @return string
     */
    protected function getRelationshipBinds(Collection $collection, $relationship, $key)
    {
        $primaries = [];

        foreach ($collection->getIterator() as $entity) {
            if (! array_key_exists($relationship, $entity->getRelationshipMap())) {
                continue;
            }

            $primaries[] = "'{$entity[$key]}'";
        }

        return $primaries;
    }

    /**
     * Get possible relationships and the properties attached to them.
     *
     * @param string $relationship
     *
     * @throws \InvalidArgumentException when requested relationship is not defined
     * @throws \RuntimeException when map structure is defined incorrectly
     *
     * @return array
     */
    public function getRelationshipMap($relationship = null)
    {
        if (is_null($relationship)) {
            return $this->relationships;
        }

        if (! array_key_exists($relationship, $this->relationships)) {
            throw new InvalidArgumentException(
                sprintf('(%s) is not defined in the relationship map on (%s)', $relationship, get_class($this))
            );
        }

        $map = $this->relationships[$relationship];

        foreach ([
            'defined_in' => ['table', 'primary', 'entity'],
            'target'     => ['table', 'primary', 'relationship']
        ] as $key => $value) {
            if (! array_key_exists($key, $map) || ! is_array($map[$key])) {
                throw new RuntimeException(
                    sprintf(
                        'Relationship (%s) should contain the (%s) key and should be of type array on (%s)',
                        $relationship, $key, get_class($this)
                    )
                );
            }

            if (! empty(array_diff($value, array_keys($map[$key])))) {
                throw new RuntimeException(
                    sprintf(
                        '(%s) for relationship (%s) should contain keys (%s) on (%s)',
                        $key, $relationship, implode(', ', $value), get_class($this)
                    )
                );
            }
        }

        return $map;
    }

    /**
     * Returns table that repository is reading from.
     *
     * @return string
     */
    abstract protected function getTable();
}
