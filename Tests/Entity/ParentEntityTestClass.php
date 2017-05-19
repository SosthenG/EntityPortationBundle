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

    /**
     * @PortableProperty(label="Âge", position="3")
     */
    public $age;

    /**
     * ParentEntityTestClass constructor.
     *
     * @param $id
     * @param $firstname
     * @param $lastname
     * @param $age
     */
    public function __construct($id = null, $firstname = null, $lastname = null, $age = null)
    {
        if (!empty($id)) $this->id = $id;
        if (!empty($firstname)) $this->firstname = $firstname;
        if (!empty($lastname)) $this->lastname = $lastname;
        if (!empty($age)) $this->age = $age;
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

    /**
     * @param mixed $id
     *
     * @return ParentEntityTestClass
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param mixed $firstname
     *
     * @return ParentEntityTestClass
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * @param mixed $lastname
     *
     * @return ParentEntityTestClass
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * @param mixed $age
     *
     * @return ParentEntityTestClass
     */
    public function setAge($age)
    {
        $this->age = $age;

        return $this;
    }

}