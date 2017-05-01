<?php
namespace SosthenG\EntityPortationBundle\Annotation;

/**
 * Annotation for the entity portation parameters
 * Must be applied only on the class
 *
 * @package SosthenG\EntityPortationBundle
 * @author  Sosthèn Gaillard <sosthen.gaillard@gmail.com>
 *
 * @Annotation
 * @Target("CLASS")
 */
class EntityPortation
{
    /**
     * The sheet title
     *
     * @var string
     */
    public $sheetTitle = '';

    /**
     * The label that will replace the property name
     *
     * @var string
     */
    public $fallBackValue = '';

    /**
     * The csv delimiter char
     *
     * @var string
     */
    public $csvDelimiter = '';
}