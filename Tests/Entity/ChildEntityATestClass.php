<?php
namespace SosthenG\EntityPortationBundle\Tests\Entity;

use SosthenG\EntityPortationBundle\Annotation\PortableProperty;

/**
 * Class ChildEntityATestClass
 *
 * @package SosthenG\EntityPortationBundle\Tests\Entity
 */
class ChildEntityATestClass extends ParentEntityTestClass
{
    /**
     * @PortableProperty(valueType="boolean")
     */
    public  $activated;

    /**
     * @PortableProperty()
     */
    private $adress;

    /**
     * @var IncludedObject
     * @PortableProperty(valueType="object", objectProperty="nom")
     */
    private $object;

    /**
     * @inheritdoc
     *
     * @param $activated
     * @param $adress
     */
    public function __construct($id, $firstname, $lastname, $age, $activated, $adress, $object)
    {
        parent::__construct($id, $firstname, $lastname, $age);
        $this->activated = $activated;
        $this->adress    = $adress;
        $this->object    = $object;
    }

    /**
     * Should not be added because the public var will already be
     *
     * @return mixed
     */
    public function getActivated()
    {
        return $this->activated;
    }

    /**
     * Should be added because the var is private
     *
     * @return mixed
     */
    public function getAdress()
    {
        return $this->adress;
    }

    /**
     * @return mixed
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param mixed $object
     *
     * @return ChildEntityATestClass
     */
    public function setObject($object)
    {
        $this->object = $object;

        return $this;
    }
}