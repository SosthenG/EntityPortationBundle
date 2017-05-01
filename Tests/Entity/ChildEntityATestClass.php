<?php
namespace SosthenG\EntityPortationBundle\Tests\Entity;

use SosthenG\EntityPortationBundle\Annotation\PortationGetter;

/**
 * Class ChildEntityATestClass
 *
 * @package SosthenG\EntityPortationBundle\Tests\Entity
 */
class ChildEntityATestClass extends ParentEntityTestClass
{
    public  $activated;

    private $adress;

    /**
     * @inheritdoc
     *
     * @param $activated
     * @param $adress
     */
    public function __construct($id, $firstname, $lastname, $age, $activated, $adress)
    {
        parent::__construct($id, $firstname, $lastname, $age);
        $this->activated = $activated;
        $this->adress    = $adress;
    }

    /**
     * Should not be added because the public var will already be
     *
     * @return mixed
     * @PortationGetter(valueType="boolean")
     */
    public function getActivated()
    {
        return $this->activated;
    }

    /**
     * Should be added because the var is private
     *
     * @return mixed
     * @PortationGetter()
     */
    public function getAdress()
    {
        return $this->adress;
    }
}