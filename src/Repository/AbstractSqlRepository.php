<?php

namespace Percy\Repository;

use Aura\Sql\ExtendedPdoInterface;
use InvalidArgumentException;
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
    public function countFromRequest(ServerRequestInterface $request, $joins = '', $conditionals = '', $end = '')
    {
        $rules = $this->parseQueryString($request->getUri()->getQuery());

        list($query, $params) = $this->buildQueryFromRules(
            $rules,
            'SELECT COUNT(*) as total FROM ',
            $joins,
            $conditionals,
            $end
        );

        return (int) $this->dbal->fetchOne($query, $params)['total'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFromRequest(
        ServerRequestInterface $request,
        $start        = 'SELECT * FROM ',
        $joins        = '',
        $conditionals = '',
        $end          = ''
    ) {
        $rules = $this->parseQueryString($request->getUri()->getQuery());

        list($query, $params) = $this->buildQueryFromRules($rules, $start, $joins, $conditionals, $end);

        if (array_key_exists('sort', $rules)) {
            $query .= sprintf(' ORDER BY %s ', $rules['sort']);
            $query .= (array_key_exists('sort_direction', $rules)) ? $rules['sort_direction'] : 'ASC';
        }

        if (array_key_exists('limit', $rules)) {
            $query .= ' LIMIT ';
            $query .= (array_key_exists('offset', $rules)) ? sprintf('%d,', $rules['offset']) : '';
            $query .= $rules['limit'];
        }

        return $this->buildCollection($this->dbal->fetchAll($query, $params))
                    ->setTotal($this->countFromRequest($request, $joins, $conditionals, $end));
    }

    /**
     * Build a base query without sorting and limits from filter rules.
     *
     * @param array  $rules
     * @param string $start
     * @param string $joins
     * @param string $conditionals
     * @param string $end
     *
     * @return array
     */
    protected function buildQueryFromRules(array $rules, $start, $joins, $conditionals, $end)
    {
        $start = $start . $this->getTable();
        $query = sprintf('%s %s %s', $start, $joins, $conditionals);

        $params = [];

        if (array_key_exists('filter', $rules)) {
            foreach ($rules['filter'] as $key => $where) {
                $keyword   = ($key === 0 || $conditionals !== '') ? ' WHERE' : ' AND';
                $delimiter = strtoupper($where['delimiter']);
                $binding   = (in_array($delimiter, ['IN', 'NOT IN'])) ? sprintf('(:%s)', $where['binding']) : ':' . $where['binding'];
                $query    .= sprintf('%s %s %s %s', $keyword, $where['field'], $delimiter, $binding);

                $params[$where['binding']] = $where['value'];
            }
        }

        $query .= " {$end}";

        return [$query, $params];
    }

    /**
     * {@inheritdoc}
     */
    public function countByField($field, $value)
    {
        $query = sprintf(
            'SELECT COUNT(*) as total FROM %s WHERE %s.%s IN (:%s)',
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
    public function getByField($field, $value)
    {
        $query = sprintf(
            'SELECT * FROM %s WHERE %s.%s IN (:%s)',
            $this->getTable(),
            $this->getTable(),
            $field,
            $field
        );

        $params = [
            $field => implode(',', (array) $value)
        ];

        return $this->buildCollection($this->dbal->fetchAll($query, $params))
                    ->setTotal($this->countByField($field, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function getRelationshipsFor(Collection $collection, array $relationships = [])
    {
        $relCollection = new Collection;

        foreach ($collection->getIterator() as $entity) {
            $rels = $entity->getRelationships();
            array_walk($rels, [$this, 'getEntityRelationships'], [
                'entity'     => $entity,
                'collection' => $relCollection,
                'include'    => $relationships
            ]);
        }

        return $relCollection;
    }

    /**
     * Attach relationships to a specific entity.
     *
     * @param string $entityType
     * @param string $relationship
     * @param array  $userData
     *
     * @return void
     */
    protected function getEntityRelationships($entityType, $relationship, array $userData)
    {
        $collection = $userData['collection'];
        $include    = $userData['include'];
        $entity     = $userData['entity'];
        $map        = $this->getRelationshipMap($relationship);

        if (! in_array($relationship, $include)) {
            return false;
        }

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

        $result = $this->dbal->fetchAll($query, [
            $map['defined_in']['entity'] => $entity[$map['defined_in']['entity']]
        ]);

        $remove = [$map['defined_in']['primary'], $map['target']['relationship']];

        foreach ($result as $resource) {
            $resource = array_filter($resource, function ($key) use ($remove) {
                return (! in_array($key, $remove));
            }, ARRAY_FILTER_USE_KEY);

            $collection->addEntity((new $entityType)->hydrate($resource));
        }
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
    public function getRelationshipMap($relationship)
    {
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
