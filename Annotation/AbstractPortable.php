<?php
namespace SosthenG\EntityPortationBundle\Annotation;

use Doctrine\Common\Annotations\Annotation\Enum;

/**
 * Abstract class for the entity portation Annotations
 *
 * @package SosthenG\EntityPortationBundle
 * @author  Sosthèn Gaillard <sosthen.gaillard@gmail.com>
 */
abstract class AbstractPortable
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
     * If empty, the reader will look for a @ var annotation
     *
     * @var string
     * @Enum({"", "string", "number", "array", "object", "boolean", "date"})
     */
    public $valueType = '';

    /**
     * If the value is of type "date", you can specify a conversion format for it
     * You need to use a php valid date format.
     *
     * @var string
     */
    public $dateFormat = 'Y-m-d';

    /**
     * For objects types, you can choose which property of the object will be used for Portation
     * Otherwise, the reader will try to use the __toString method.
     * Or, the object will be converted to an array and this array will be imploded.
     *
     * @var string
     */
    public $objectProperty = ''; // Todo : use this

    /**
     * On which type of portation this attribute/method should be used (default is both)
     *
     * @var string
     * @Enum({"EXPORT", "IMPORT", "BOTH"})
     */
    public $portations = 'BOTH';
}