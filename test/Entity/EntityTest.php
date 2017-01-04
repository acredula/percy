<?php

namespace Percy\Test\Entity;

use Percy\Entity\Collection;
use Percy\Test\Asset\EntityStub;
use Percy\Test\Asset\EntityWithGeneralScopeStub;
use Percy\Test\Asset\EmptyEntityStub;

class EntityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts that the entity can a provide a type mapping.
     */
    public function testEntityCanProvideTypeMapping()
    {
        $mapping = (new EntityStub)->getMapping();

        $this->assertSame($mapping, [
            'uuid'           => null,
            'some_field'     => 'string',
            'another_field'  => null,
            'do_not_persist' => null
        ]);
    }

    /**
     * Asserts that the entity can a provide validation rules.
     */
    public function testEntityCanProvideValidationRules()
    {
        $validator = (new EntityStub)->getValidator();

        $this->assertSame($validator, 'Acme\Validator\EntityValidator');
    }

    /**
     * Asserts that the entity returns property with relationship.
     */
    public function testEntityReturnsRelationships()
    {
        $rels = (new EntityStub)->getRelationshipMap();

        $this->assertSame($rels, ['some_relationship' => EntityStub::class]);
    }

    /**
     * Asserts that the entity sets and gets data with type coercion.
     */
    public function testEntityCanSetAndGetDataWithTypeCoercion()
    {
        $entity = new EntityStub;

        $original = ['uuid' => 'something', 'some_field' => 1, 'another_field' => 'some_value'];
        $expected = ['uuid' => 'something', 'some_field' => '1', 'another_field' => 'some_value'];

        $entity->hydrate($original);

        $this->assertTrue(isset($entity['some_field']));
        $this->assertTrue(isset($entity['another_field']));

        $this->assertSame($entity['some_field'], '1');
        $this->assertSame($entity['another_field'], 'some_value');

        $this->assertSame($expected, $entity->getData());

        unset($entity['some_field']);
        unset($entity['another_field']);
        unset($entity['uuid']);

        $this->assertEmpty($entity->getData());
    }

    /**
     * Asserts that an exception is thrown when attempting to set a field that is not mapped.
     */
    public function testExceptionIsThrownWhenSettingUnmappedProperty()
    {
        $this->setExpectedException('InvalidArgumentException', '(something) is not an accepted field for (Percy\Test\Asset\EntityStub)');

        $entity = new EntityStub;
        $entity['something'] = 'something';
    }

    /**
     * Asserts that exception is thrown when getting a field that has not been set.
     */
    public function testExceptionIsThrownWhenGettingFieldThatIsNotSet()
    {
        $this->setExpectedException('InvalidArgumentException', 'Undefined offset (something) on (Percy\Test\Asset\EntityStub)');

        $entity = new EntityStub;
        $something = $entity['something'];
    }

    /**
     * Asserts that an entity can return the correct decorators.
     */
    public function testEntityReturnsCorrectDecorators()
    {
        $entity = new EntityStub;

        $this->assertSame($entity->getDecorators(), [
            0 => ['Acme\Decorator' => ['some_field']],
            1 => [],
            2 => [],
            3 => []
        ]);

        $this->assertSame($entity->getDecorators(0), ['Acme\Decorator' => ['some_field']]);
    }

    /**
     * Asserts that the entity builds the correct array structure.
     */
    public function testEntityBuildsCorrectArray()
    {
        $entity = new EntityStub;

        $entity->hydrate([
            'uuid'           => 'uuid',
            'some_field'     => 'some_field',
            'another_field'  => 'another_field',
            'do_not_persist' => 'do_not_persist'
        ]);

        $this->assertSame($entity->toArray(), [
            'uuid'           => 'uuid',
            'some_field'     => 'some_field',
            'another_field'  => 'another_field',
            'do_not_persist' => 'do_not_persist',
            '_relationships' => []
        ]);
    }

    /**
     * Asserts that an entity can add a relationship.
     */
    public function testEntityCanAddRelationship()
    {
        $collection = $this->createMock(Collection::class);

        $entity = new EntityStub;

        $entity->addRelationship('test', $collection);

        $this->assertArrayHasKey('test', $entity->getRelationships());
        $this->assertSame($collection, $entity->getRelationships()['test']);
    }

    /**
     * Asserts that an exception is thrown when trying to read with no scope.
     */
    public function testExceptionIsThrownWhenIncorrectReadScope()
    {
        $this->setExpectedException('Percy\Exception\ScopeException');
        $entity = new EntityWithGeneralScopeStub;

        $entity->getData(['wrong_scope']);
    }

    /**
     * Asserts that an exception is thrown when trying to write with no scope.
     */
    public function testExceptionIsThrownWhenIncorrectWriteScope()
    {
        $this->setExpectedException('Percy\Exception\ScopeException');
        $entity = new EntityWithGeneralScopeStub;

        $entity->getData(['read_scope'], true);
    }

    /**
     * Asserts that fields are handled correctly with scopes.
     */
    public function testEntityHandlesFieldsCorrectlyWithScopes()
    {
        $entity = new EntityWithGeneralScopeStub;

        $entity->hydrate([
            'field1' => 'blah', 'field2' => 'blah', 'field3' => 'blah'
        ]);

        $this->assertSame([
            'field1' => 'blah', 'field2' => 'blah'
        ], $entity->getData(['read_scope', 'write_scope', 'test.read', 'test.write'], true));

        $this->assertSame([
            'field1' => 'blah'
        ], $entity->getData(['read_scope', 'write_scope', 'test.read'], true));

        $this->assertSame([
            'field2' => 'blah', 'field3' => 'blah',
        ], $entity->getData(['read_scope']));
    }

    /**
     * Asserts that a relationship is skipped when it fails scope check.
     */
    public function testRelationshipIsSkippedWhenFailsScopeCheck()
    {
        $collection = new Collection;

        $mock = $this->createMock('Percy\Entity\AbstractEntity');
        $mock->expects($this->once())->method('toArray')->will($this->throwException(new \Percy\Exception\ScopeException));

        $collection->addEntity($mock);

        $entity = new EntityStub;
        $entity->addRelationship('test', $collection);

        $this->assertTrue(empty($entity->toArray()['_relationships']));
    }
}
