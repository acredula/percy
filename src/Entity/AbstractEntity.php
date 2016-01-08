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
     * @var string
     */
    protected $validator;

    /**
     * {@inheritdoc}
     */
    public function toArray(array $scopes = [])
    {
        $data = [
            '_relationships' => []
        ];

        foreach ($this->getRelationships() as $relationship) {
            $data['_relationships'][$relationship] = [
                '_links' => [
                    'self' => [
                        'href' => sprintf(
                            '/%s/%s/%s',
                            $this->getDataSource(),
                            $this[$this->getPrimary()],
                            $relationship
                        )
                    ]
                ]
            ];
        }

        $data['_relationships']['all'] = [
            '_links' => [
                'self' => [
                    'href' => sprintf(
                        '/%s/%s/%s',
                        $this->getDataSource(),
                        $this[$this->getPrimary()],
                        implode(',', $this->getRelationships())
                    )
                ]
            ]
        ];

        return array_merge($this->getData($scopes), $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getData(array $scopes = [])
    {
        // @todo filter by scopes
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

        $this->data[$offset] = (empty($value)) ? null : $value;
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
