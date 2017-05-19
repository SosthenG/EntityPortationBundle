<?php
namespace SosthenG\EntityPortationBundle;

use Doctrine\Common\Annotations\AnnotationReader;
use Liuggio\ExcelBundle\Factory;
use SosthenG\EntityPortationBundle\Annotation\EntityPortationReader;
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
    protected static $defaultOptions = array('label' => '', 'visible' => true, 'position' => 'auto', 'getter' => '', 'setter' => '');

    /**
     * @var array
     */
    protected static $replaceablePrefixes = array('get', 'is', 'has', 'my', 'set', 'add');

    /**
     * @var array
     */
    protected static $extensions = array('CSV'          => 'csv',
                                         'Excel5'       => 'xls',
                                         'Excel2007'    => 'xlsx',
                                         'XML'          => 'xml',
                                         'HTML'         => 'html',
                                         'OpenDocument' => 'ods',
                                         'PDF'          => 'pdf');

    /**
     * Get a well formatted column name (without prefix or useless chars)
     *
     * @param string $name
     * @param bool   $replacePrefix
     *
     * @return string
     */
    public static function getColumnName($name, $replacePrefix = true)
    {
        if ($replacePrefix) $name = preg_replace('/^(?>'.implode('|', self::$replaceablePrefixes).')/', '', $name);

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
     * @var string
     */
    protected $_fallbackValue = '';

    /**
     * @var string
     */
    protected $_csvDelimiter = '';

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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setAllVisible($visible = true)
    {
        foreach ($this->_columns as $column => $options) {
            $options['visible'] = $visible;
        }

        return $this;
    }

    /**
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    protected function _addColumn($column, array $options)
    {
        if (!is_string($column))
            throw new \InvalidArgumentException("This is not a valid column name.");

        $this->_columns[$column] = self::formatOptionsArray($options);

        return $this;
    }

    /**
     * @return string
     */
    public function getFallbackValue()
    {
        return $this->_fallbackValue;
    }

    /**
     * Set the fallback value if a cell value is empty for an entity.
     * Default is to put an empty string in the cell.
     *
     * @param string $fallbackValue
     *
     * @return $this
     */
    public function setFallbackValue($fallbackValue = '')
    {
        $this->_fallbackValue = $fallbackValue;

        return $this;
    }

    /**
     * @return string
     */
    public function getCsvDelimiter()
    {
        return $this->_csvDelimiter;
    }

    /**
     * @param $delimiter
     *
     * @return $this
     */
    public function setCsvDelimiter($delimiter)
    {
        $this->_csvDelimiter = $delimiter;

        return $this;
    }

    /**
     * Please ensure your class annotation is on the parent class if you have differents entities.
     * If not, this method will only takes the first entity parameters
     *
     * @param object|string $entity
     */
    protected function _setPropertiesFromEntitiesAnnotation($entity)
    {
        $reader = new EntityPortationReader(new AnnotationReader());

        $properties = $reader->extractEntityParameters($entity);

        if (isset($this->_sheetTitle) && !empty($properties->sheetTitle)) $this->_sheetTitle = $properties->sheetTitle;
        if (!empty($properties->fallBackValue)) $this->_fallbackValue = $properties->fallBackValue;
        if (!empty($properties->csvDelimiter)) $this->_csvDelimiter = $properties->csvDelimiter;

        $this->_annotate = !empty($properties);
    }
}