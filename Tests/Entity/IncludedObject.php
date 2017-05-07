<?php
namespace SosthenG\EntityPortationBundle\Tests\Entity;

/**
 * Class IncludedObject
 *
 * @package SosthenG\EntityPortationBundle\Tests\Entity
 * @author  Sosthèn Gaillard <sosthen.gaillard@gmail.com>
 */
class IncludedObject
{
    private $id;

    private $nom;

    public function __construct($id, $nom){
        $this->id = $id;
        $this->nom = $nom;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return IncludedObject
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * @param mixed $nom
     *
     * @return IncludedObject
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }
}