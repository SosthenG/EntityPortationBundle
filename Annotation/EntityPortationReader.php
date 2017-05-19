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
     * @var array
     */
    protected $_columns = array();

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
     * @param &array $elements
     */
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

    /**
     * @param \ReflectionClass  $reflectionClass
     * @param bool              $replaceIfExists
     * @param bool              $annotate
     */
    protected function extractColumnsFromProperties(\ReflectionClass $reflectionClass, $replaceIfExists, $annotate) {

        if ($annotate) // If annotate mode, get all properties to check their annotations
            $properties = $reflectionClass->getProperties();
        else
            $properties = $reflectionClass->getProperties(\ReflectionProperty::IS_PUBLIC);

        // Sort the properties by there original class, to have the parent properties first, then the children's in order of inheritance
        $this->_sortElements($properties);

        foreach ($properties as $property) {
            $columnName = AbstractPortation::getColumnName($property->getName(), false);
            $options = array();
            if ($annotate) {
                $annotation = $this->_reader->getPropertyAnnotation($property, PortableProperty::class);
                if ($annotation !== null) {
                    $options = (array)$annotation;
                    if (!(array_key_exists($columnName, $this->_columns)) || $replaceIfExists) {
                        $this->_columns[$columnName] = AbstractPortation::formatOptionsArray($options);
                    }
                }
            }
            else {
                if (!(array_key_exists($columnName, $this->_columns)) || $replaceIfExists) {
                    $this->_columns[$columnName] = AbstractPortation::formatOptionsArray($options);
                }
            }
        }
    }

    /**
     * @param \ReflectionClass  $reflectionClass
     * @param bool              $replaceIfExists
     * @param bool              $annotate
     */
    protected function extractColumnsFromMethods(\ReflectionClass $reflectionClass, $replaceIfExists, $annotate) {
        if ($annotate) // If annotate mode, get all methods to check their annotations
            $methods = $reflectionClass->getMethods();
        else
            $methods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);

        // Sort the methods by there original class, to have the parent methods first, then the children's in order of inheritance
        $this->_sortElements($methods);

        foreach ($methods as $method) {
            $columnName = AbstractPortation::getColumnName($method->getName());
            $options = array();
            if (substr($method->getName(), 0, 2) != '__' && (!(array_key_exists($columnName, $this->_columns)) || $replaceIfExists)) {
                if ($annotate) {
                    $annotation = $this->_reader->getMethodAnnotation($method, PortableMethod::class);
                    if ($annotation !== null) {
                        $options = (array)$annotation;

                        if (empty($options['methodType'])) $options['methodType'] = $this->_getMethodType($method);

                        if (empty($options['getter']) && $options['methodType'] == 'GETTER') $options['getter'] = $method->getName();
                        if (empty($options['setter']) && $options['methodType'] == 'SETTER') $options['setter'] = $method->getName();
                        $this->_columns[$columnName] = AbstractPortation::formatOptionsArray($options);
                    }
                }
                else {
                    if (!(array_key_exists($columnName, $this->_columns)) || $replaceIfExists) {
                        $options['methodType'] = $this->_getMethodType($method);
                        if ($options['methodType'] == 'GETTER') $options['getter'] = $method->getName();
                        if ($options['methodType'] == 'SETTER') $options['setter'] = $method->getName();
                        $this->_columns[$columnName] = AbstractPortation::formatOptionsArray($options);
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
        $getterPrefixes = implode('|', Export::getReplaceablePrefixes());
        $setterPrefixes = implode('|', Import::getReplaceablePrefixes());
        if (preg_match("/^(?>".$getterPrefixes.")/", $name)) {
            return "GETTER";
        }
        elseif (preg_match("/^(?>".$setterPrefixes.")/", $name)) {
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
     * @param object|string $entity
     * @param bool          $replaceIfExists Default = true
     * @param bool          $annotate Default = false
     *
     * @return array The extracted columns
     */
    public function extractColumnsFromEntity($entity, $replaceIfExists = true, $annotate = false)
    {
        if (!is_object($entity) && !class_exists($entity))
            throw new \InvalidArgumentException("The parameter of the extraction method must be an object or a class name.");

        $reflectionClass = new \ReflectionClass($entity);

        $this->extractColumnsFromProperties($reflectionClass, $replaceIfExists, $annotate);

        $this->extractColumnsFromMethods($reflectionClass, $replaceIfExists, $annotate);

        return $this->_columns;
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