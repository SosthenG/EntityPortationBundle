<?php
namespace SosthenG\EntityPortationBundle\Tests\Entity;

use SosthenG\EntityPortationBundle\Annotation\EntityPortation;
use SosthenG\EntityPortationBundle\Annotation\PortationGetter;

/**
 * Class ParentEntityTestClass
 *
 * @package SosthenG\EntityPortationBundle\Tests\Entity
 * @EntityPortation(sheetTitle="MySheetTitle", fallBackValue="N/A")
 */
class ParentEntityTestClass
{
    private $id;

    private $firstname;

    private $lastname;

    private $age;

    /**
     * ParentEntityTestClass constructor.
     *
     * @param $id
     * @param $firstname
     * @param $lastname
     * @param $age
     */
    public function __construct($id, $firstname, $lastname, $age)
    {
        $this->id        = $id;
        $this->firstname = $firstname;
        $this->lastname  = $lastname;
        $this->age       = $age;
    }

    /**
     * @return mixed
     * @PortationGetter(label="Identifiant", position="0")
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     * @PortationGetter(label="Prénom", position="1")
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @return mixed
     * @PortationGetter(label="Nom", position="2")
     */
    public function getLastname()
    {
        return $this->lastname;
    }
}