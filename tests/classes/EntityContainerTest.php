<?php

/**
 * Created by Gorlum 12.08.2016 15:24
 */
use Entity\EntityContainer;


class EntityContainerTestModel extends \Entity\EntityModel {
  protected $newProperties = array(
    'int' => array(
      P_DB_FIELD_TYPE => TYPE_INTEGER,
    ),
    'float' => array(
      P_DB_FIELD_TYPE => TYPE_FLOAT,
    ),
    'str1' => array(
      P_DB_FIELD_TYPE => TYPE_STRING,
    ),
    'notype' => array(
    ),
  );
}

/**
 * Class EntityContainerTest
 * @coversDefaultClass Entity\EntityContainer
 */
class EntityContainerTest extends PHPUnit_Framework_TestCase {

  /**
   * @var EntityContainer $object
   */
  protected $object;

  /**
   * @var \Common\GlobalContainer
   */
  protected $gc;

  /**
   * @var EntityContainerTestModel $model
   */
  protected $model;

  public function setUp() {
    parent::setUp();
    $this->gc = new \Common\GlobalContainer();
    classSupernova::$gc = $this->gc;

    $this->gc->types = function ($c) {
      return new \Common\Types();
    };


    $this->model = new EntityContainerTestModel($this->gc);
    $this->object = new Entity\EntityContainer($this->model);
  }

  public function tearDown() {
    unset($this->object);
    unset($this->gc);
    parent::tearDown();
  }

  /**
   * @covers ::__construct
   * @covers ::setModel
   * @covers ::getModel
   */
  public function testGetModel() {
    $this->assertAttributeEquals($this->model, 'model', $this->object);
    $this->assertEquals($this->model, $this->object->getModel());

    $otherModel = new EntityContainerTestModel($this->gc);
    $this->object->setModel($otherModel);
    $this->assertAttributeEquals($otherModel, 'model', $this->object);
    $this->assertEquals($otherModel, $this->object->getModel());

    $otherObject = new Entity\EntityContainer($otherModel);
    $this->assertAttributeEquals($otherModel, 'model', $otherObject);
    $this->assertEquals($otherModel, $otherObject->getModel());
  }

  /**
   * @covers ::__set
   * @covers ::clear
   * @covers ::isChanged
   * @covers ::getDeltas
   */
  public function test__set() {
    // Integer properties SHOULD be affected
    $this->object->__set('int', 2);

    $this->assertFalse($this->object->isChanged());

    $this->object->__set('int', 3);
    $this->assertEquals(3, $this->object->int);
    $this->assertAttributeEquals(array('int' => 2), 'original', $this->object);
    $this->assertAttributeEquals(array('int' => 1), 'delta', $this->object);

    $this->assertTrue($this->object->isChanged());
    $this->assertEquals(array('int' => 1), $this->object->getDeltas());

    $delta = array('int' => 1, 'float' => 4.0);
    // Float properties SHOULD be affected
    $this->object->__set('float', 3.0);
    $this->object->__set('float', 7.0);
    $this->assertEquals(7.0, $this->object->float);
    $this->assertAttributeEquals(array('int' => 2, 'float' => 3.0), 'original', $this->object);
    $this->assertAttributeEquals($delta, 'delta', $this->object);

    // Properties typed as STRING should not be affected
    $this->object->__set('str1', '1');
    $this->assertAttributeEquals(array('int' => 3, 'float' => 7.0, 'str1' => '1'), 'values', $this->object);
    $this->assertAttributeEquals(array('int' => 2, 'float' => 3.0, 'str1' => '1'), 'original', $this->object);
    $this->assertAttributeEquals($delta, 'delta', $this->object);
    $this->object->__set('str1', '3');
    $this->assertAttributeEquals(array('int' => 3, 'float' => 7.0, 'str1' => '3'), 'values', $this->object);
    $this->assertAttributeEquals(array('int' => 2, 'float' => 3.0, 'str1' => '1'), 'original', $this->object);
    $this->assertAttributeEquals(array('int' => 1, 'float' => 4.0, 'str1' => '3'), 'delta', $this->object);

    // Not typed properties defaults to STRING
    $this->object->__set('notype', '2');
    $this->object->__set('notype', '4');
    $this->assertAttributeEquals(array('int' => 3, 'float' => 7.0, 'str1' => '3', 'notype' => '4'), 'values', $this->object);
    $this->assertAttributeEquals(array('int' => 2, 'float' => 3.0, 'str1' => '1', 'notype' => '2'), 'original', $this->object);
    $this->assertAttributeEquals(array('int' => 1, 'float' => 4.0, 'str1' => '3', 'notype' => '4'), 'delta', $this->object);

    $this->object->clear();
    $this->assertAttributeEquals(array(), 'values', $this->object);
    $this->assertAttributeEquals(array(), 'original', $this->object);
    $this->assertAttributeEquals(array(), 'delta', $this->object);
  }

}