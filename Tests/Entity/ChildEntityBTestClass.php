<?php
namespace SosthenG\EntityPortationBundle\Tests\Entity;

use SosthenG\EntityPortationBundle\Annotation\PortableProperty;

/**
 * Class ChildEntityBTestClass
 *
 * @package SosthenG\EntityPortationBundle\Tests\Entity
 */
class ChildEntityBTestClass extends ParentEntityTestClass
{
    /**
     * @PortableProperty()
     */
    private $phoneNumber;

    /**
     * @inheritdoc
     *
     * @param $phoneNumber
     */
    public function __construct($id, $firstname, $lastname, $age, $phoneNumber)
    {
        parent::__construct($id, $firstname, $lastname, $age);
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * Should be added because the var is private
     *
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }
}