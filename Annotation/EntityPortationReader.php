<?php
namespace SosthenG\EntityPortationBundle\Annotation;

use Doctrine\Common\Annotations\Reader;
use SosthenG\EntityPortationBundle\AbstractPortation;

/**
 * Class EntityPortation
 *
 * @package SosthenG\EntityPortationBundle\Annotation
 * @author  Sosthèn Gaillard <sosthen.gaillard@gmail.com>
 */
class EntityPortationReader
{
    /**
     * @var Reader
     */
    protected $_reader;

    /**
     * EntityPortationReader constructor.
     *
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->_reader = $reader;
    }

    /**
     * Extract all possible columns for the given entity.
     *
     * By default, the last child annotation will replace the parents ones
     * Be careful, if two differents childs redefine the annotation differently, only one of them will be keeped.
     * To change this behavior, pass (bool)false as a second argument
     *
     * @param object $entity
     * @param bool   $replaceIfExists Default = true
     *
     * @return array The extracted columns
     */
    public function extractColumnsFromEntity($entity, $replaceIfExists = true)
    {
        if (!is_object($entity))
            throw new \InvalidArgumentException("The parameter of the extraction method must be an object.");

        $columns          = array();
        $reflectionObject = new \ReflectionObject($entity);

        $methods = $reflectionObject->getMethods(\ReflectionMethod::IS_PUBLIC);

        // Sort the methods by there original class, to have the parent methods first, then the children's in order of inheritance
        uasort($methods, function(\ReflectionMethod $a, \ReflectionMethod $b) {
            if ($a->getDeclaringClass() == $b->getDeclaringClass()) {
                return 0;
            }
            elseif (!$a->getDeclaringClass()
                       ->getParentClass()
            ) {
                return -1;
            }

            return 1;
        });

        foreach ($methods as $reflectionMethod) {
            $columnName = AbstractPortation::getColumnName($reflectionMethod->getName());
            if (substr($reflectionMethod->getName(), 0, 2) != '__' && (!($keyExists = array_key_exists($columnName, $columns)) || $replaceIfExists)) {

                $annotation = $this->_reader->getMethodAnnotation($reflectionMethod, PortationGetter::class);
                if ($annotation !== null) {
                    $options = (array)$annotation;

                    if (empty($options['getter'])) $options['getter'] = $reflectionMethod->getName();
                    if (empty($options['valueType']) && is_callable(array($reflectionMethod, "getReturnType"))) $options['valueType'] = $reflectionMethod->getReturnType();

                    if ($keyExists) {
                        $columns[$columnName] = array_merge($columns[$columnName], $options);
                    }
                    else {
                        $columns[$columnName] = $options;
                    }
                }
            }
        }

        return $columns;
    }

    /**
     * @param object $entity
     *
     * @return null|object
     */
    public function extractEntityParameters($entity)
    {
        $refl = new \ReflectionClass($entity);
        do {
            $annotation = $this->_reader->getClassAnnotation($refl, EntityPortation::class);
            $parent     = $refl->getParentClass();
            if ($parent) $refl = new \ReflectionClass($parent);
        } while ($annotation === null && $parent);

        return $annotation;
    }
}