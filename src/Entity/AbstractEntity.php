<?php

namespace Percy\Entity;

use InvalidArgumentException;
use RuntimeException;

abstract class AbstractEntity implements EntityInterface
{
    /**
     * @var array
     */
    protected $data = [];

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
        if (property_exists($this, 'mapping') && is_array($this->mapping)) {
            return array_combine(array_keys($this->mapping), array_map(function ($value) {
                return (array_key_exists('type', $value)) ? $value['type'] : null;
            }, $this->mapping));
        }

        throw new RuntimeException(sprintf('(%s) expects a (mapping) property to be defined', get_class($this)));
    }

    /**
     * {@inheritdoc}
     */
    public function getRelationshipKeys()
    {
        if (property_exists($this, 'relationships')) {
            return $this->relationships;
        }

        throw new RuntimeException(sprintf('(%s) expects a (relationships) property to be defined', get_class($this)));
    }

    /**
     * {@inheritdoc}
     */
    public function getValidationRules()
    {
        if (property_exists($this, 'mapping') && is_array($this->mapping)) {
            return array_combine(array_keys($this->mapping), array_map(function ($value) {
                return (array_key_exists('validation', $value)) ? $value['validation'] : null;
            }, $this->mapping));
        }

        throw new RuntimeException(sprintf('(%s) expects a (mapping) property to be defined', get_class($this)));
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

        if (! array_key_exists($offset, $mapping) && ! in_array($offset, $this->getRelationshipKeys())) {
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
