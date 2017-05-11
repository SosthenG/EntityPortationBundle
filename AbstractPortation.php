<?php
namespace SosthenG\EntityPortationBundle;

use Liuggio\ExcelBundle\Factory;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class AbstractPortation
 *
 * @package SosthenG\EntityPortationBundle
 * @author  Sosthèn Gaillard <sosthen.gaillard@gmail.com>
 */
abstract class AbstractPortation
{
    /* STATIC PART */

    /**
     * @var array
     */
    public static $defaultOptions = array('label' => '', 'visible' => true, 'position' => 'auto', 'getter' => '', 'setter' => '');

    /**
     * @var array
     */
    public static $replacablePrefix = array('get', 'is', 'has', 'my', 'set', 'add');

    /* END OF STATIC PART */

    /**
     * @var Factory
     */
    protected $_phpExcelFactory;

    /**
     * @var \PHPExcel
     */
    protected $_phpExcelObject;

    /**
     * @var array
     */
    protected $_entities = array();

    /**
     * @var array
     */
    protected $_columns = array();

    /**
     * @var bool
     */
    protected $_annotate = false;

    /**
     * @var TranslatorInterface
     */
    protected $_translator = null;

    /**
     * Exportation constructor.
     *
     * @param Factory             $phpExcelFactory PhpExcel Factory service
     * @param TranslatorInterface $translator
     */
    public function __construct(Factory $phpExcelFactory, TranslatorInterface $translator)
    {
        $this->_phpExcelFactory = $phpExcelFactory;
        $this->_translator = $translator;
    }

    /**
     * Get a well formatted column name (without prefix or useless chars)
     *
     * @param string $name
     *
     * @return string
     */
    public static function getColumnName($name, $replacePrefix = true)
    {
        if ($replacePrefix) $name = preg_replace('/^(?>'.implode('|', self::$replacablePrefix).')/', '', $name);

        $name = trim($name, '_');

        return $name;
    }

    /**
     * Format an array to match a valid options array
     *
     * @param array $options
     *
     * @return array
     */
    public static function formatOptionsArray(array $options)
    {
        return array_merge(self::$defaultOptions, array_intersect_key($options, self::$defaultOptions));
    }

    /**
     * @return \PHPExcel
     */
    public function getPhpExcelObject()
    {
        return $this->_phpExcelObject;
    }

    /**
     * Set the PHPExcel Properties such as Creator, Title, Description, ...
     *
     * @param \PHPExcel_DocumentProperties $properties
     *
     * @return AbstractPortation
     */
    public function setProperties(\PHPExcel_DocumentProperties $properties)
    {
        $this->_phpExcelObject->setProperties($properties);

        return $this;
    }

    /**
     * Get the PHPExcel Properties defined
     * You can define each properties directly from this result
     *
     * @return \PHPExcel_DocumentProperties
     */
    public function getProperties()
    {
        return $this->_phpExcelObject->getProperties();
    }

    /**
     * @return array The entities registered in the Portation object
     */
    public function getEntities()
    {
        return $this->_entities;
    }

    /**
     * @return array The columns detected
     */
    public function getColumns()
    {
        return $this->_columns;
    }

    /**
     * @param string $column
     *
     * @return AbstractPortation
     *
     * @throws \OutOfBoundsException
     */
    public function resetColumnDefaultOptions($column)
    {
        if (!is_string($column) || !array_key_exists($column, $this->_columns))
            throw new \OutOfBoundsException("This column does not exist.");

        $this->_columns[$column] = self::$defaultOptions;

        return $this;
    }

    /**
     * @param $column
     *
     * @return array
     *
     * @throws \OutOfBoundsException
     */
    public function getColumnOptions($column)
    {
        if (!is_string($column) || !array_key_exists($column, $this->_columns))
            throw new \OutOfBoundsException("This column does not exist.");

        return $this->_columns[$column];
    }

    /**
     * @param string $column
     * @param array  $options
     *
     * @return AbstractPortation
     *
     * @throws \OutOfBoundsException
     */
    public function setColumnOptions($column, array $options)
    {
        if (!is_string($column) || !array_key_exists($column, $this->_columns))
            throw new \OutOfBoundsException("This column does not exist.");

        $this->_columns[$column] = self::formatOptionsArray($options);

        return $this;
    }

    /**
     * @param string $column
     * @param string $option
     * @param mixed  $value
     *
     * @return AbstractPortation
     *
     * @throws \OutOfBoundsException
     */
    public function setColumnOption($column, $option, $value)
    {
        if (!is_string($column) || !array_key_exists($column, $this->_columns))
            throw new \OutOfBoundsException("This column does not exist.");

        if (!is_string($option) || !array_key_exists($option, self::$defaultOptions))
            throw new \OutOfBoundsException("This option does not exist.");

        $this->_columns[$column][$option] = $value;

        return $this;
    }

    /**
     * @param bool $visible
     *
     * @return AbstractPortation
     */
    public function setAllVisible($visible = true)
    {
        foreach ($this->_columns as $column => $options) {
            $options['visible'] = $visible;
        }

        return $this;
    }

    /**
     * @return AbstractPortation
     */
    public function setAllPositionAuto()
    {
        foreach ($this->_columns as $column => $options) {
            $options['position'] = 'auto';
        }

        return $this;
    }

    /**
     * @param array $columns
     *
     * @return AbstractPortation
     *
     * @throws \OutOfBoundsException
     */
    protected function _addColumns(array $columns)
    {
        foreach ($columns as $column => $options) {
            if (!array_key_exists($column, $this->_columns))
                $this->_addColumn($column, $options);
            else
                $this->setColumnOptions($column, $options);
        }

        return $this;
    }

    /**
     * @param string $column
     * @param array  $options
     *
     * @return AbstractPortation
     */
    protected function _addColumn($column, array $options)
    {
        if (!is_string($column))
            throw new \InvalidArgumentException("This is not a valid column name.");

        $this->_columns[$column] = self::formatOptionsArray($options);

        return $this;
    }
}