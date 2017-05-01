<?php
namespace SosthenG\EntityPortationBundle\Tests\Entity;

/**
 * Class ChildEntityBTestClass
 *
 * @package SosthenG\EntityPortationBundle\Tests\Entity
 */
class OtherEntityTestClass
{
    private $unknownVar;

    /**
     * OtherEntityTestClass constructor.
     *
     * @param $unknownVar
     */
    public function __construct($unknownVar) { $this->unknownVar = $unknownVar; }

    /**
     * @return mixed
     */
    public function getUnknownVar()
    {
        return $this->unknownVar;
    }
}