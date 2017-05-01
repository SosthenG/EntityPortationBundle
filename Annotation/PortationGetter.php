<?php
namespace SosthenG\EntityPortationBundle\Annotation;

/**
 * Annotation for the entity portation parameters
 * Must be applied only on public properties or getter methods
 *
 * @package SosthenG\EntityPortationBundle
 * @author  Sosthèn Gaillard <sosthen.gaillard@gmail.com>
 *
 * @Annotation
 * @Target("METHOD")
 */
class PortationGetter
{
    /**
     * The label that will replace the property name
     *
     * @var string
     */
    public $label = '';

    /**
     * Is the field visible when exporting or not
     *
     * @var bool
     */
    public $visible = true;

    /**
     * Column position, default "auto" will keep the class order
     *
     * @var string
     */
    public $position = 'auto';

    /**
     * The value type, useful if you want to convert boolean to a string for example
     *
     * @var string
     */
    public $valueType = '';
}