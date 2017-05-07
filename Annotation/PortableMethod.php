<?php
namespace SosthenG\EntityPortationBundle\Annotation;

use Doctrine\Common\Annotations\Annotation\Enum;

/**
 * Annotation for the entity portation parameters
 * Must be applied only on methods you want portables
 *
 * @package SosthenG\EntityPortationBundle
 * @author  Sosthèn Gaillard <sosthen.gaillard@gmail.com>
 *
 * @Annotation
 * @Target("METHOD")
 */
class PortableMethod extends AbstractPortable
{
    /**
     * The type of the method
     * If empty, the reader will guess it from the method name and according to the PSR standard
     * Example : the "getTest()" method is a getter, the "setTest($test)" method is a setter
     *
     * @var string
     * @Enum({"GETTER", "SETTER"})
     */
    public $methodType = '';

    /**
     * The property getted/setted by this method
     * If empty, the reader will guess it from the method name and according to the PSR standard
     * Example : the "getTest()" need to return the "$test" or "$_test" property
     *
     * @var string
     */
    public $property = '';
}