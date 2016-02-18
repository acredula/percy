<?php

namespace Percy\Store;

use Aura\Filter\FilterFactory;
use Aura\SqlQuery\QueryFactory;
use Aura\Sql\ExtendedPdo;
use PDOException;
use Percy\Entity\Collection;
use Percy\Entity\EntityInterface;

class SqlStore extends AbstractStore
{
    /**
     * @var \Aura\Sql\ExtendedPdo
     */
    protected $dbal;

    /**
     * @var \Aura\SqlQuery\QueryFactory
     */
    protected $query;

    /**
     * Construct.
     *
     * @param \Aura\Sql\ExtendedPdo      $dbal
     * @param \Aura\Filter\FilterFactory $filter
     */
    public function __construct(ExtendedPdo $dbal, FilterFactory $filter)
    {
        $this->dbal  = $dbal;
        $this->query = new QueryFactory('mysql');
        parent::__construct($filter);
    }

    /**
     * {@inheritdoc}
     */
    public function create(Collection $collection, array $scopes = [])
    {
        $this->decorate($collection, StoreInterface::ON_CREATE);
        $this->validate($collection);
        return $this->collectionIterator($collection, 'insertEntity');
    }

    /**
     * Insert an entity to the database.
     *
     * @param \Percy\Entity\EntityInterface $entity
     *
     * @return void
     */
    protected function insertEntity(EntityInterface $entity)
    {
        $insert = $this->query->newInsert();
        $insert->into($entity->getDataSource());
        $insert->cols($entity->getData([]));

        $this->dbal->perform($insert->getStatement(), $insert->getBindValues());
    }

    /**
     * {@inheritdoc}
     */
    public function read(Collection $collection, array $scopes = [])
    {
        // mysql need do nothing with the data on a read request
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function update(Collection $collection, array $scopes = [])
    {
        $this->decorate($collection, StoreInterface::ON_UPDATE);
        $this->validate($collection);
        return $this->collectionIterator($collection, 'updateEntity');
    }

    /**
     * Update an entity in the database.
     *
     * @param \Percy\Entity\EntityInterface $entity
     *
     * @return void
     */
    protected function updateEntity(EntityInterface $entity)
    {
        $update = $this->query->newUpdate();
        $update->table($entity->getDataSource());
        $update->cols($entity->getData([]));
        $update->where(sprintf('%s = ?', $entity->getPrimary()), $entity[$entity->getPrimary()]);

        $this->dbal->perform($update->getStatement(), $update->getBindValues());
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Collection $collection, array $scopes = [])
    {
        $this->decorate($collection, StoreInterface::ON_DELETE);
        return $this->collectionIterator($collection, 'deleteEntity');
    }

    /**
     * Delete an entity from the database. Be aware, this handles hard deletes.
     * To handle soft deletes, extend this class and overload this method.
     *
     * @param \Percy\Entity\EntityInterface $entity
     *
     * @return void
     */
    protected function deleteEntity(EntityInterface $entity)
    {
        $delete = $this->query->newDelete();
        $delete->from($entity->getDataSource());
        $delete->where(sprintf('%s = ?', $entity->getPrimary()), $entity[$entity->getPrimary()]);

        $this->dbal->perform($delete->getStatement(), $delete->getBindValues());
    }

    /**
     * Iterate a collection with the correct callback.
     *
     * @param \Percy\Entity\Collection $collection
     * @param string                   $callable
     *
     * @return boolean
     */
    protected function collectionIterator(Collection $collection, $callable)
    {
        $this->dbal->beginTransaction();

        try {
            foreach ($collection->getIterator() as $entity) {
                call_user_func_array([$this, $callable], [$entity]);
            }
        } catch (PDOException $e) {
            $this->dbal->rollBack();
            return false;
        }

        $this->dbal->commit();
        return true;
    }
    
    /**
     * Persist relationships to data store.
     *
     * @param \Percy\Entity\EntityInterface $entity
     * @param array                         $rels
     * @param array                         $map
     *
     * @return void
     */
    public function relationships(EntityInterface $entity, array $rels, array $map)
    {
        $this->dbal->beginTransaction();

        foreach ($rels as $rel) {
            $data = [
                $map['defined_in']['primary']  => $entity[$map['defined_in']['entity']],
                $map['target']['relationship'] => $rel
            ];

            $insert = $this->query->newInsert();
            $insert->into($map['defined_in']['table']);
            $insert->cols($data);

            $this->dbal->perform($insert->getStatement(), $insert->getBindValues());
        }

        $this->dbal->commit();
    }
}
