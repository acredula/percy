<?php

namespace Percy\Test\Entity;

use Percy\Test\Asset\EntityStub;
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
            'some_field'    => 'string',
            'another_field' => null
        ]);
    }

    /**
     * Asserts that exception is thrown when mapping not defined for type mapping.
     */
    public function testExceptionIsThrownWhenMappingNotDefinedForTypeMapping()
    {
        $this->setExpectedException('RuntimeException');
        (new EmptyEntityStub)->getMapping();
    }

    /**
     * Asserts that the entity can a provide validation rules.
     */
    public function testEntityCanProvideValidationRules()
    {
        $mapping = (new EntityStub)->getValidationRules();

        $this->assertSame($mapping, [
            'some_field'    => 'rules',
            'another_field' => null
        ]);
    }

    /**
     * Asserts that exception is thrown when mapping not defined for validation.
     */
    public function testExceptionIsThrownWhenMappingNotDefinedForValidation()
    {
        $this->setExpectedException('RuntimeException');
        (new EmptyEntityStub)->getValidationRules();
    }

    /**
     * Asserts that the entity returns property with relationship.
     */
    public function testEntityReturnsRelationships()
    {
        $rels = (new EntityStub)->getRelationships();

        $this->assertSame($rels, ['some_relationship']);
    }

    /**
     * Asserts that exception is thrown when relationships property is not defined.
     */
    public function testExceptionIsThrownWhenRelationshipsNotDefined()
    {
        $this->setExpectedException('RuntimeException');
        (new EmptyEntityStub)->getRelationships();
    }

    /**
     * Asserts that the entity sets and gets data with type coercion.
     */
    public function testEntityCanSetAndGetDataWithTypeCoercion()
    {
        $entity = new EntityStub;

        $original = ['some_field' => 1, 'another_field' => 'some_value'];
        $expected = ['some_field' => '1', 'another_field' => 'some_value'];

        $entity->hydrate($original);

        $this->assertTrue(isset($entity['some_field']));
        $this->assertTrue(isset($entity['another_field']));

        $this->assertSame($entity['some_field'], '1');
        $this->assertSame($entity['another_field'], 'some_value');

        $this->assertSame($expected, $entity->toArray());

        unset($entity['some_field']);
        unset($entity['another_field']);

        $this->assertEmpty($entity->toArray());
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

    public function testEntityProxiesOutToCollectionWhenItFindsOne()
    {
        $collection = $this->getMock('Percy\Entity\Collection');
        $collection->expects($this->once())->method('toArray')->will($this->returnValue([]));

        $entity = new EntityStub;

        $entity['some_relationship'] = $collection;

        $this->assertSame($entity->toArray(), ['some_relationship' => []]);
    }
}
