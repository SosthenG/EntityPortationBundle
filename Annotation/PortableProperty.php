<?php
namespace SosthenG\EntityPortationBundle\Annotation;

/**
 * Annotation for the entity portation parameters
 * Must be applied only on the properties you want portables
 *
 * @package SosthenG\EntityPortationBundle
 * @author  Sosthèn Gaillard <sosthen.gaillard@gmail.com>
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class PortableProperty extends AbstractPortable
{
    /**
     * The getter method for this property
     * If empty, the reader will guess it from the property name and according to the PSR standard
     * Example for a "$test" property, the getter had to be : "getTest()"
     *
     * @var string
     */
    public $getter = '';

    /**
     * The setter method for this property
     * If empty, the reader will guess it from the property name and according to the PSR standard
     * Example for a "$test" property, the getter had to be : "setTest($test)"
     *
     * @var string
     */
    public $setter = '';
}