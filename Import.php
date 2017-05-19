<?php
namespace SosthenG\EntityPortationBundle;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use Prophecy\Exception\Doubler\MethodNotFoundException;
use SosthenG\EntityPortationBundle\Annotation\EntityPortationReader;

/**
 * Class Import
 *
 * @package SosthenG\EntityPortationBundle
 * @author  Sosthèn Gaillard <sosthen.gaillard@gmail.com>
 */
class Import extends AbstractPortation
{
    /**
     * @var array
     */
    protected static $replaceablePrefixes = array('set'); // TODO : add ?

    public static function getReplaceablePrefixes()
    {
        return self::$replaceablePrefixes;
    }

    /**
     * @var string
     */
    protected $_highestColumn;

    /**
     * @var int
     */
    protected $_highestRow;

    /**
     * @var \PHPExcel_Worksheet
     */
    protected $_sheet;

    /**
     * @var string
     */
    protected $_class;

    /**
     * @var array
     */
    protected $_headers = array();

    /**
     * @var array
     */
    protected $_columnsNotFound = array();



    /**
     * @param string $extension
     *
     * @return string The file type from the extension
     */
    protected function _getFileTypeFromExtension($extension)
    {
        $types = array_flip(self::$extensions);

        if (empty($types[$extension]))
            throw new \InvalidArgumentException("Couldn't guess the file type from the extension ".$extension);

        return $types[$extension];
    }

    /**
     * Create an ArrayCollection of entities for the given class and from the given filename
     *
     * If your file do not have headers, your columns will then have to match the entity Portables properties
     *
     * @param string $file
     * @param string $class
     * @param bool   $firstRowIsHeader Set it to false if your file doesn't have headers.
     *
     * @return object[]|ArrayCollection
     *
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public function createEntitesFromFile($file, $class, $firstRowIsHeader = true, $replaceIfExists = true)
    {
        $entities = new ArrayCollection();
        $fileInfos = pathinfo($file);
        $fileType = $this->_getFileTypeFromExtension($fileInfos['extension']);

        if (!class_exists($class))
            throw new \InvalidArgumentException("Couldn't find the class ".$class);

        $this->_class = $class;

        $annotationReader = new EntityPortationReader(new AnnotationReader());

        $this->_setPropertiesFromEntitiesAnnotation($class);

        $this->_columns = $annotationReader->extractColumnsFromEntity($class, $replaceIfExists, $this->_annotate);

        /** @var $reader \PHPExcel_Reader_Abstract */
        $reader = $this->_phpExcelFactory->createReader($fileType);

        /** @var $reader \PHPExcel_Reader_CSV */
        if ($fileType == 'CSV' && !empty($this->_csvDelimiter)) $reader->setDelimiter($this->_csvDelimiter);
        /** @var $reader \PHPExcel_Reader_Abstract */

        $reader->setReadDataOnly(true);

        $this->_phpExcelObject = $reader->load($file);

        $this->_sheet = $this->_phpExcelObject->getActiveSheet();
        $this->_highestRow = $this->_sheet->getHighestDataRow();
        $this->_highestColumn = $this->_sheet->getHighestDataColumn();
        $firstRow = 1;

        if ($firstRowIsHeader) {
            for ($col = 'A'; $col <= $this->_highestColumn; $col++) {
                $this->_headers[$col] = $this->_sheet->getCell($col.$firstRow);
            }
            $firstRow++;
        }

        for ($row = $firstRow; $row <= $this->_highestRow; $row++) {
            $entities->add($this->_createEntityFromRow($row));
        }

        return $entities;
    }

    protected function _getColumnKeyFromLabel($label)
    {
        foreach ($this->_columns as $key => $column) {
            if ($column['label'] == $label) return $key;
        }
        return null;
    }

    protected function addNotFoundValue($col, $row, $value)
    {
        if (!array_key_exists($row, $this->_columnsNotFound)) $this->_columnsNotFound[$row] = array();
        $this->_columnsNotFound[$row][$col] = $value;
    }

    public function getNotFoundValues()
    {
        return $this->_columnsNotFound;
    }

    /**
     * @param object $object
     * @param string $property
     *
     * @return mixed
     */
    protected function _checkPossibleSetters($object, $property)
    {
        $refl = new \ReflectionObject($object);
        foreach (self::$replaceablePrefixes as $prefix) {
            if ($refl->hasMethod($prefix.ucfirst($property)) && $refl->getMethod($prefix.ucfirst($property))->isPublic()) {
                return $prefix.ucfirst($property);
            }
        }
        return null;
    }

    protected function _createEntityFromRow($row) {
        $entity = new $this->_class();

        for ($col = 'A'; $col <= $this->_highestColumn; $col++) {
            $value = $this->_sheet->getCell($col.$row)->getValue();
            // If the value is empty or is the fallback value, continue
            if (empty($value) || (!empty($this->_fallbackValue) && $value == $this->_fallbackValue)) continue;

            if (!empty($this->_headers[$col]) && ($columnKey = $this->_getColumnKeyFromLabel($this->_headers[$col])) !== null) {
                $setter = $this->_columns[$columnKey]['setter'];
                // Using setter
                if (!empty($setter) && is_callable(array($entity, $setter))) {
                    $entity->$setter($value);
                }
                // Using public property
                elseif (!empty($entity->$columnKey)) {
                    $entity->$columnKey = $value;
                }
                // Find possible setters
                elseif (($setter = $this->_checkPossibleSetters($entity, $columnKey)) !== null) {
                    $entity->$setter($value);
                }
                // Cannot set property
                else {
                    $this->addNotFoundValue($col, $row, $value);
                }
            }
            else {
                // TODO : without header, use position (and maybe a type detection...)
                $this->addNotFoundValue($col, $row, $value);
            }
        }

        return $entity;
    }
}