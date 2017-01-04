<?php

namespace Percy\Entity;

use InvalidArgumentException;
use RuntimeException;
use Percy\Exception\ScopeException;
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
    protected $relationshipMap = [];

    /**
     * @var array
     */
    protected $relationships = [];

    /**
     * @var array
     */
    protected $decorators = [];

    /**
     * @var string
     */
    protected $validator;

    /**
     * @var string
     */
    protected $readScope;

    /**
     * @var string
     */
    protected $writeScope;

    /**
     * {@inheritdoc}
     */
    public function toArray(array $scopes = [])
    {
        $data = [
            '_relationships' => []
        ];

        foreach ($this->getRelationships() as $key => $value) {
            try {
                $data['_relationships'][$key] = $value->toArray($scopes);
            } catch (ScopeException $e) {
                continue;
            }
        }

        return array_merge($this->getData($scopes, false), $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getData(array $scopes = [], $toPersist = false)
    {
        if (! is_null($this->getReadScope()) && ! in_array($this->getReadScope(), $scopes)) {
            throw new ScopeException(sprintf(
                '(%s) scope needed to read (%s) resource', $this->getReadScope(), get_called_class()
            ));
        }

        if (! is_null($this->getWriteScope()) && ! in_array($this->getWriteScope(), $scopes) && $toPersist === true) {
            throw new ScopeException(sprintf(
                '(%s) scope needed to write (%s) resource', $this->getWriteScope(), get_called_class()
            ));
        }

        $data = [];

        foreach ($this->mapping as $prop => $options) {
            if (array_key_exists('persist', $options) && $options['persist'] === false && $toPersist === true) {
                continue;
            }

            if (array_key_exists('read', $options) && ! in_array($options['read'], $scopes)) {
                continue;
            }

            if (array_key_exists('write', $options) && ! in_array($options['write'], $scopes) && $toPersist === true) {
                continue;
            }

            if (array_key_exists($prop, $this->data)) {
                $data[$prop] = $this->data[$prop];
            }
        }

        return $data;
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
    public function getRelationshipMap()
    {
        return $this->relationshipMap;
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
    public function addRelationship($relationship, Collection $collection)
    {
        $this->relationships[$relationship] = $collection;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDecorators($action = null)
    {
        $decorators = array_replace([
            StoreInterface::ON_CREATE => [],
            StoreInterface::ON_READ   => [],
            StoreInterface::ON_UPDATE => [],
            StoreInterface::ON_DELETE => []
        ], $this->decorators);

        return (is_null($action)) ? $decorators : $decorators[$action];
    }

    /**
     * {@inheritdoc}
     */
    public function getValidator()
    {
        return $this->validator;
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

        if (! array_key_exists($offset, $mapping)) {
            throw new InvalidArgumentException(
                sprintf('(%s) is not an accepted field for (%s)', $offset, get_class($this))
            );
        }

        if (array_key_exists($offset, $mapping) && ! is_null($mapping[$offset]) && ! is_null($value)) {
            settype($value, $mapping[$offset]);
        }

        $this->data[$offset] = (! isset($value)) ? null : $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        if (array_key_exists($offset, $this->data)) {
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

    /**
     * {@inheritdoc}
     */
    public function getReadScope()
    {
        return $this->readScope;
    }

    /**
     * {@inheritdoc}
     */
    public function getWriteScope()
    {
        return $this->writeScope;
    }
}
