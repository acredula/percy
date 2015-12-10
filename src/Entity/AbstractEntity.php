<?php

namespace Percy\Entity;

use InvalidArgumentException;
use RuntimeException;
use Percy\Store\StoreInterface;

abstract class AbstractEntity implements EntityInterface
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var array
     */
    protected $mapping = [];

    /**
     * @var array
     */
    protected $relationships = [];

    /**
     * @var array
     */
    protected $decorators = [];

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        foreach ($this->data as $key => $value) {
            if ($value instanceof Collection) {
                $this->data[$key] = $value->toArray();
            }
        }

        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getMapping()
    {
        return array_combine(array_keys($this->mapping), array_map(function ($value) {
            return (array_key_exists('type', $value)) ? $value['type'] : null;
        }, $this->mapping));
}

    /**
     * {@inheritdoc}
     */
    public function getRelationships()
    {
        return $this->relationships;
    }

    /**
     * {@inheritdoc}
     */
    public function getDecorators($action = null)
    {
        $decorators = array_merge([
            StoreInterface::ON_CREATE => [],
            StoreInterface::ON_READ   => [],
            StoreInterface::ON_UPDATE => [],
            StoreInterface::ON_DELETE => []
        ], $this->decorators);

        return (is_null($action)) ? $decorators : $decorators[$type];
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationRules()
    {
        return array_combine(array_keys($this->mapping), array_map(function ($value) {
            return (array_key_exists('validation', $value)) ? $value['validation'] : null;
        }, $this->mapping));
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate(array $data)
    {
        foreach ($data as $key => $value) {
            $this[$key] = $value;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $mapping = $this->getMapping();

        if (! array_key_exists($offset, $mapping) && ! array_key_exists($offset, $this->getRelationships())) {
            throw new InvalidArgumentException(
                sprintf('(%s) is not an accepted field for (%s)', $offset, get_class($this))
            );
        }

        if (array_key_exists($offset, $mapping) && ! is_null($mapping[$offset])) {
            settype($value, $mapping[$offset]);
        }

        $this->data[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if (isset($this->data[$offset])) {
            return $this->data[$offset];
        }

        throw new InvalidArgumentException(
            sprintf('Undefined offset (%s) on (%s)', $offset, get_class($this))
        );
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
}
