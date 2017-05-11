<?php
namespace SosthenG\EntityPortationBundle;

use Doctrine\Common\Collections\ArrayCollection;

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
    public function createEntitesFromFile($file, $class, $firstRowIsHeader = true)
    {
        $entities = new ArrayCollection();
        $fileInfos = pathinfo($file);
        $fileType = $this->_getFileTypeFromExtension($fileInfos['extension']);

        if (!class_exists($class))
            throw new \InvalidArgumentException("Couldn't find the class ".$class);

        $this->_class = $class;

        /** @var $reader \PHPExcel_Reader_Abstract */
        $reader = $this->_phpExcelFactory->createReader($fileType);

        $reader->setReadDataOnly(true);

        $this->_phpExcelObject = $reader->load($file);

        $this->_sheet = $this->_phpExcelObject->getActiveSheet();
        $this->_highestRow = $this->_sheet->getHighestDataRow();
        $this->_highestColumn = $this->_sheet->getHighestDataColumn();
        $firstRow = 1;

        if ($firstRowIsHeader) {
            for ($col = 'A'; $col <= $this->_highestColumn; $col++) {
                $this->_headers[] = $this->_sheet->getCellByColumnAndRow($col, $firstRow);
            }
            $firstRow++;
        }

        for ($row = $firstRow; $row < $this->_highestRow; $row++) {
            $entities->add($this->_createEntityFromRow($row));
        }

        return $entities;
    }

    protected function _createEntityFromRow($row) {
        $entity = new $this->_class();
        $values = array();

        for ($col = 'A'; $col <= $this->_highestColumn; $col++) {
            $values = $this->_sheet->getCellByColumnAndRow($col, $row);
        }

        // Todo : set entity properties from values array

        return $entity;
    }
}