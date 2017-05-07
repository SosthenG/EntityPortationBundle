<?php
namespace SosthenG\EntityPortationBundle\Annotation;

use Doctrine\Common\Annotations\Reader;
use SosthenG\EntityPortationBundle\AbstractPortation;
use SosthenG\EntityPortationBundle\Export;
use SosthenG\EntityPortationBundle\Import;

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

    protected function _sortElements(&$elements) {
        uasort($elements, function($a, $b) {
            /**
             * @var $a \ReflectionProperty|\ReflectionMethod
             * @var $b \ReflectionProperty|\ReflectionMethod
             */
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
    }

    protected function extractColumnsFromProperties(&$columns, \ReflectionObject $reflectionObject, $replaceIfExists, $annotate) {

        if ($annotate) // If annotate mode, get all properties to check their annotations
            $properties = $reflectionObject->getProperties();
        else
            $properties = $reflectionObject->getProperties(\ReflectionProperty::IS_PUBLIC);

        // Sort the properties by there original class, to have the parent properties first, then the children's in order of inheritance
        $this->_sortElements($properties);

        foreach ($properties as $property) {
            $columnName = AbstractPortation::getColumnName($property->getName());
            $options = array();
            if ($annotate) {
                $annotation = $this->_reader->getPropertyAnnotation($property, PortableProperty::class);
                if ($annotation !== null) {
                    $options = (array)$annotation;
                    if (!(array_key_exists($columnName, $columns)) || $replaceIfExists) {
                        $columns[$columnName] = AbstractPortation::formatOptionsArray($options);
                    }
                }
            }
            else {
                if (!(array_key_exists($columnName, $columns)) || $replaceIfExists) {
                    $columns[$columnName] = AbstractPortation::formatOptionsArray($options);
                }
            }
        }
    }

    protected function extractColumnsFromMethods(&$columns, \ReflectionObject $reflectionObject, $replaceIfExists, $annotate) {
        if ($annotate) // If annotate mode, get all methods to check their annotations
            $methods = $reflectionObject->getMethods();
        else
            $methods = $reflectionObject->getMethods(\ReflectionMethod::IS_PUBLIC);

        // Sort the methods by there original class, to have the parent methods first, then the children's in order of inheritance
        $this->_sortElements($methods);

        foreach ($methods as $method) {
            $columnName = AbstractPortation::getColumnName($method->getName());
            $options = array();
            if (substr($method->getName(), 0, 2) != '__' && (!(array_key_exists($columnName, $columns)) || $replaceIfExists)) {
                if ($annotate) {
                    $annotation = $this->_reader->getMethodAnnotation($method, PortableMethod::class);
                    if ($annotation !== null) {
                        $options = (array)$annotation;

                        if (empty($options['methodType'])) $options['methodType'] = $this->_getMethodType($method);

                        if (empty($options['getter']) && $options['methodType'] == 'GETTER') $options['getter'] = $method->getName();
                        if (empty($options['setter']) && $options['methodType'] == 'SETTER') $options['setter'] = $method->getName();

                        if (empty($options['valueType']) && is_callable(array($method, "getReturnType"))) $options['valueType'] = $method->getReturnType();

                        $columns[$columnName] = AbstractPortation::formatOptionsArray($options);
                    }
                }
                else {
                    if (!(array_key_exists($columnName, $columns)) || $replaceIfExists) {
                        $options['methodType'] = $this->_getMethodType($method);
                        if ($options['methodType'] == 'GETTER') $options['getter'] = $method->getName();
                        if ($options['methodType'] == 'SETTER') $options['setter'] = $method->getName();
                        if (is_callable(array($method, "getReturnType"))) $options['valueType'] = $method->getReturnType();
                        $columns[$columnName] = AbstractPortation::formatOptionsArray($options);
                    }
                }
            }
        }
    }

    /**
     * @param \ReflectionMethod $method
     *
     * @return string
     */
    protected function _getMethodType(\ReflectionMethod $method)
    {
        $name = $method->getName();
        $getterPrefixes = implode('|', Export::$replacablePrefix);
        $setterPrefixes = implode('|', Import::$replacablePrefix);
        if (preg_match("/^".$getterPrefixes."/", $name)) {
            return "GETTER";
        }
        elseif (preg_match("/^".$setterPrefixes."/", $name)) {
            return "SETTER";
        }
        return "";
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
     * @param bool   $annotate Default = false
     *
     * @return array The extracted columns
     */
    public function extractColumnsFromEntity($entity, $replaceIfExists = true, $annotate = false)
    {
        if (!is_object($entity))
            throw new \InvalidArgumentException("The parameter of the extraction method must be an object.");

        $columns          = array();
        $reflectionObject = new \ReflectionObject($entity);

        $this->extractColumnsFromProperties($columns, $reflectionObject, $replaceIfExists, $annotate);

        $this->extractColumnsFromMethods($columns, $reflectionObject, $replaceIfExists, $annotate);

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