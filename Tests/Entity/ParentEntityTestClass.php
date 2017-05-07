<?php
namespace SosthenG\EntityPortationBundle\Tests\Entity;

use SosthenG\EntityPortationBundle\Annotation\EntityPortation;
use SosthenG\EntityPortationBundle\Annotation\PortableProperty;

/**
 * Class ParentEntityTestClass
 *
 * @package SosthenG\EntityPortationBundle\Tests\Entity
 * @EntityPortation(sheetTitle="MySheetTitle", fallBackValue="N/A", csvDelimiter=";")
 */
class ParentEntityTestClass
{
    /**
     * @PortableProperty(label="Identifiant", position="0")
     */
    private $id;

    /**
     * @PortableProperty(label="Prénom", position="1")
     */
    private $firstname;

    /**
     * @PortableProperty(label="Nom", position="2")
     */
    private $lastname;

    public $age;

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
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @return mixed
     */
    public function getLastname()
    {
        return $this->lastname;
    }
}