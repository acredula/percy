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
        list($query, $params) = $this->buildQueryFromRules($rules, true);

        return (int) $this->dbal->fetchOne($query, $params)['total'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFromRequest(ServerRequestInterface $request)
    {
        $rules = $this->parseQueryString($request->getUri()->getQuery());

        list($query, $params) = $this->buildQueryFromRules($rules);

        if (array_key_exists('sort', $rules) && ! array_key_exists('search', $rules)) {
            $entity    = $this->getEntityType();
            $entity    = new $entity;
            $mapping   = $entity->getMapping();
            $whitelist = array_keys($mapping);

            $query .= $this->buildSortPart($rules['sort'], $this->getTable(), $whitelist);
        }

        if (array_key_exists('search', $rules) && $this->acceptableField($rules['search']['fields'])) {
            $query .= sprintf(' ORDER BY MATCH (%s) AGAINST (:match_bind) > :score_bind', $rules['search']['fields']);
        }

        if (array_key_exists('limit', $rules)) {
            $query .= ' LIMIT ';
            $query .= (array_key_exists('offset', $rules)) ? sprintf('%d,', $rules['offset']) : '';
            $query .= sprintf('%d', $rules['limit']);
        }

        $query = trim(preg_replace('!\s+!', ' ', $query));

        $collection = $this->buildCollection($this->dbal->fetchAll($query, $params))
                           ->setTotal($this->countFromRequest($request));

        $this->decorate($collection, StoreInterface::ON_READ);

        return $collection;
    }

    /**
     * Build the sort part of the query.
     *
     * @param array|string $sorts
     * @param string       $table
     * @param array        $whitelist
     *
     * @return string
     */
    protected function buildSortPart($sorts, $table, array $whitelist)
    {
        if (is_string($sorts) && $sorts === 'RAND()') {
            return ' ORDER BY RAND()';
        }

        if (! is_array($sorts)) {
            return '';
        }

        $fields = [];

        foreach ($sorts as $sort) {
            $field = explode('.', $sort['field']);

            if (count($field) !== 2) {
                throw new InvalidArgumentException('Sort paramater is formatted incorrectly');
            }

            if ($field[0] !== $table && count($sorts) > 1) {
                continue;
            }
            
            if ($field[0] !== $table && count($sorts) < 2 && $field[0] === $this->getTable()) {
                continue;
            }
            
            if ($field[0] !== $table && count($sorts) < 2) {
                throw new InvalidArgumentException(
                    sprintf('(%s) is not a whitelisted field to sort by', $sort['field'])
                );
            }

            if (! in_array($field[1], $whitelist)) {
                throw new InvalidArgumentException(
                    sprintf('(%s) is not a whitelisted field to sort by', $sort['field'])
                );
            }

            $fields[] = sprintf('%s %s', $sort['field'], strtoupper($sort['direction']));
        }

        return (empty($fields)) ? '' : sprintf(' ORDER BY %s', implode(', ', $fields));
    }

    /**
     * Build a base query without sorting and limits from filter rules.
     *
     * @param array   $rules
     * @param boolean $count
     *
     * @return array
     */
    protected function buildQueryFromRules(array $rules, $count = false)
    {
        $start = ($count === false) ? 'SELECT * FROM ' : 'SELECT *, COUNT(*) as total FROM ';
        $query = $start . $this->getTable();

        $params = [];

        if (array_key_exists('filter', $rules)) {
            foreach ($rules['filter'] as $key => $where) {
                $this->acceptableField($where['field']);

                $keyword   = ($key === 0) ? ' WHERE' : ' AND';
                $delimiter = strtoupper($where['delimiter']);
                $binding   = (in_array($delimiter, ['IN', 'NOT IN'])) ? sprintf('(:%s)', $where['binding']) : ':' . $where['binding'];
                $query    .= sprintf('%s %s %s %s', $keyword, $where['field'], $delimiter, $binding);

                $params[$where['binding']] = $where['value'];
            }
        }

        if (array_key_exists('search', $rules) && $this->acceptableField($rules['search']['fields'])) {
            $keyword = (array_key_exists('filter', $rules)) ? ' AND' : ' WHERE';
            $query  .= sprintf('%s MATCH (%s) AGAINST (:match_bind IN BOOLEAN MODE)', $keyword, $rules['search']['fields']);
            $query  .= sprintf(' HAVING MATCH (%s) AGAINST (:match_bind) > :score_bind', $rules['search']['fields']);

            $params['match_bind'] = $rules['search']['term'];
            $params['score_bind'] = (array_key_exists('minscore', $rules)) ? $rules['minscore'] : 0;
        }

        return [$query, $params];
    }

    /**
     * Asserts that a field is acceptable to filter on.
     *
     * @param string $name
     *
     * @return boolean
     */
    protected function acceptableField($name)
    {
        $entity = $this->getEntityType();
        $entity = new $entity;

        foreach (explode(',', $name) as $field) {
            if (! array_key_exists($name, $entity->getMapping())) {
                throw new InvalidArgumentException(
                    sprintf('(%s) is not a whitelisted field to filter, search or sort by', $name)
                );
            }
        }

        return true;
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
            $field => $value
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
            $field => $value
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
        if (count($collection) === 0) {
            return;
        }
        
        if (is_null($include)) {
            return;
        }

        $rels = $collection->getIterator()->current()->getRelationshipMap();

        $rules = ($request instanceof ServerRequestInterface)
               ? $this->parseQueryString($request->getUri()->getQuery())
               : [];

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

            // @todo allow for further filtering of rels via request

            if (array_key_exists('sort', $rules)) {
                $whitelist = [];

                if (array_key_exists($key, $rels)) {
                    $entity    = $rels[$key];
                    $entity    = new $entity;
                    $mapping   = $entity->getMapping();
                    $whitelist = array_keys($mapping);
                }

                $query .= $this->buildSortPart($rules['sort'], $map['target']['table'], $whitelist);
            }

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
