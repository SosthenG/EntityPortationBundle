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
}