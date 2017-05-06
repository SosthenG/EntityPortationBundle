<?php
namespace SosthenG\EntityPortationBundle;

use Doctrine\Common\Annotations\AnnotationReader;
use SosthenG\EntityPortationBundle\Annotation\EntityPortationReader;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Converts an array of entities to excel
 *
 * @package SosthenG\EntityPortationBundle
 */
class Export extends AbstractPortation
{
    /**
     * @var string
     */
    protected $_sheetTitle = '';

    /**
     * @var string
     */
    protected $_fallbackValue = '';

    /**
     * @var string
     */
    protected $_csvDelimiter = '';

    /**
     * @var bool|null
     */
    protected $_entitiesHaveSameClass = null;

    /**
     * @param object[] $entities The entity array
     */
    public function setEntities(array $entities)
    {
        if (count($entities) < 1)
            throw new \InvalidArgumentException("The entity array must not be empty.");

        $this->_entities = $entities;

        // TODO : use theses functions on the initial array, before assigning
        if (!$this->_isAllEntitiesObjects())
            throw new \InvalidArgumentException("The entities must be objects.");

        if (!$this->_hasCommonParent())
            throw new \InvalidArgumentException("Entities must have a common parent.");

        $this->_setColumnsFromAccessibleProperties();

        if (count($this->_columns) < 1)
            throw new \InvalidArgumentException("Entities have no accessible parameters.");

        $this->_phpExcelObject = $this->_phpExcelFactory->createPHPExcelObject();

        $this->_setExportPropertiesFromEntitiesAnnotation($entities);
    }

    /**
     * Stream the file as Response
     *
     * The $outputType must be one of these values : "Excel5", "Excel2007", "CSV", "HTML", "OpenDocument"
     * Check the PHPExcel documentation to see other available types.
     *
     * @param string $outputType
     * @param string $output File name (and directory if needed) to output
     *
     * @return StreamedResponse
     *
     * @throws \OutOfBoundsException
     */
    public function getResponse($outputType, $output)
    {
        $this->_createPHPExcelObject();

        // If there is no extension, add the good one
        if (preg_match('/\.[a-zA-Z]+$/', $output) === 0) {
            $output .= $this->_getExtension($outputType);
        }

        $writer   = $this->_phpExcelFactory->createWriter($this->_phpExcelObject, $outputType);

        if ($outputType == 'CSV' && !empty($this->_csvDelimiter)) {
            /** @var $writer \PHPExcel_Writer_CSV */
            $writer->setDelimiter($this->_csvDelimiter);
        }

        $response = $this->_phpExcelFactory->createStreamedResponse($writer);
        // adding headers
        $dispositionHeader = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $output
        );
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    /**
     * Save the file to $output
     *
     * The $outputType must be one of these values : "Excel5", "Excel2007", "CSV", "HTML", "XML", "OpenDocument"
     * "PDF" can also be available, check the PHPExcel documentation.
     *
     * @param string $outputType
     * @param string $output File name (and directory if needed) to output
     *
     * @return string $output
     *
     * @throws \OutOfBoundsException
     * @throws \PHPExcel_Writer_Exception
     */
    public function saveAsFile($outputType, $output)
    {
        $this->_createPHPExcelObject();

        // If there is no extension, add the good one
        if (preg_match('/\.[a-zA-Z]+$/', $output) === 0) {
            $output .= $this->_getExtension($outputType);
        }

        $writer = $this->_phpExcelFactory->createWriter($this->_phpExcelObject, $outputType);
        // TODO : user parameters for writer

        if ($outputType == 'CSV' && !empty($this->_csvDelimiter)) {
            /** @var $writer \PHPExcel_Writer_CSV */
            $writer->setDelimiter($this->_csvDelimiter);
        }

        $writer->save($output);

        return $output;
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
     * @return Export
     */
    public function setFallbackValue($fallbackValue = '')
    {
        $this->_fallbackValue = $fallbackValue;

        return $this;
    }

    /**
     * @return string
     */
    public function getSheetTitle()
    {
        return $this->_sheetTitle;
    }

    /**
     * @param string $title
     *
     * @return Export
     */
    public function setSheetTitle($title)
    {
        $this->_sheetTitle = $title;

        return $this;
    }

    /**
     * Please ensure your class annotation is on the parent class if you have differents entities.
     * If not, this method will only takes the first entity parameters
     *
     * @param array $entities
     */
    protected function _setExportPropertiesFromEntitiesAnnotation(array $entities)
    {
        $reader = new EntityPortationReader(new AnnotationReader());

        $properties = $reader->extractEntityParameters($entities[0]);

        if (!empty($properties->sheetTitle)) $this->_sheetTitle = $properties->sheetTitle;
        if (!empty($properties->fallBackValue)) $this->_fallbackValue = $properties->fallBackValue;
        if (!empty($properties->csvDelimiter)) $this->_csvDelimiter = $properties->csvDelimiter;
    }

    /**
     * @param $outputType
     *
     * @return string The extension that match the type or an empty string
     */
    protected function _getExtension($outputType)
    {
        $extensions = array('CSV'          => '.csv',
                            'Excel5'       => '.xls',
                            'Excel2007'    => '.xlsx',
                            'XML'          => '.xml',
                            'HTML'         => '.html',
                            'OpenDocument' => '.ods',
                            'PDF'          => '.pdf',
        );

        if (empty($extensions[$outputType]))
            throw new \InvalidArgumentException("This filename has no extension and the output type is invalid.");

        return $extensions[$outputType];
    }

    /**
     * Generate the PHPExcel for the given entities
     * Automatically called when getResponse or saveAsfile are called.
     */
    protected function _createPHPExcelObject()
    {
        // Deletes the sheet if it has already been defined
        $this->_phpExcelObject->disconnectWorksheets();
        $this->_phpExcelObject->createSheet();

        $sheet   = $this->_phpExcelObject->setActiveSheetIndex(0);
        $linePos = '1';

        // Sort the columns to match the "position" option
        //$this->_sortColumns(); // TODO Consider remove if the other solution works

        $autoColumns = array();
        foreach ($this->_columns as $column => $options) {
            if ($options['position'] == 'auto') {
                $autoColumns[$column] = $options;
            }
            elseif ($options['visible']) {
                $label = !empty($options['label']) ? $options['label'] : $column;

                $columnPos = \PHPExcel_Cell::stringFromColumnIndex($options['position']);
                if (!empty($sheet->getCell($columnPos . $linePos)
                                 ->getValue())
                )
                    throw new \OutOfBoundsException("There is a position conflict, two columns asked for the same index.");

                $sheet->setCellValue($columnPos . $linePos, $label);
            }
        }

        $autoPos = 'A';
        foreach ($autoColumns as $column => $options) {
            if ($options['visible']) {
                $label = !empty($options['label']) ? $options['label'] : $column;

                // Get first available position
                do {
                    $columnPos = $autoPos++;
                } while (!empty($sheet->getCell($columnPos . $linePos)
                                      ->getValue()));

                $sheet->setCellValue($columnPos . $linePos, $label);
            }
        }

        foreach ($this->_entities as $entity) {
            $linePos++;
            $columnPos = 'A';
            foreach ($this->_columns as $columnName => $options) {
                if ($options['visible']) {
                    $getter = $options['getter'];
                    $value  = $this->_translator->trans($this->_fallbackValue);

                    if (is_callable(array($entity, $getter)))
                        $value = $this->_convertValue($entity->$getter(), $options);

                    $sheet->setCellValue($columnPos . $linePos, $value);

                    $columnPos++;
                }
            }
        }

        if (!empty($this->_sheetTitle)) {
            $sheet->setTitle($this->_sheetTitle);
        }
        elseif (!empty($title = $this->_phpExcelObject->getProperties()
                                                      ->getTitle())
        ) {
            $sheet->setTitle($title);
        }
    }

    /**
     * Recursive method that convert a given entity property value to string and try to translate it
     *
     * If the value is an object, the __toString method will be called if defined, or the object will be converted to array =>
     * If the value is an array, implode it to a string with a comma separator
     * If the value is a boolean, replace it with the booleanValues (you can change these with the "setBooleanValue" method)
     * This method will try to translate strings with the translator service.
     *
     * @param mixed $value
     * @param array $options
     *
     * @return string
     */
    protected function _convertValue($value, array $options = array())
    {
        if (is_object($value)) {
            $refl = new \ReflectionObject($value);
            if ($refl->hasMethod('__toString')) $value = $refl->getMethod('__toString')
                                                              ->invoke($value);
            else $value = (array)$value;
        }

        if (is_array($value)) {
            $value = array_map(array($this, '_convertValue'), $value);
            $value = implode(', ', $value);
        }

        if (!empty($options)) {
            if ($options['valueType'] == 'boolean') {
                $value = $value ? $this->_translator->trans($this->_booleanValues[1]) : $this->_translator->trans($this->_booleanValues[0]);
            }
        }

        if (is_string($value)) {
            $value = $this->_translator->trans($value);
        }

        return $value;
    }

    /**
     * Detect the entities accessible properties and define the output columns from them.
     * In case of different objects (but inheriting from the same class), properties of all the objects will be used
     */
    protected function _setColumnsFromAccessibleProperties()
    {
        $reader = new EntityPortationReader(new AnnotationReader());

        // If all entities are from the exact same class, just add the columns of the first one which are the sames for the others
        if ($this->_entitiesAreInstanceOfSameClass()) {
            $columns = $reader->extractColumnsFromEntity($this->_entities[0]);
            $this->addColumns($columns);
        }
        // Else, merge all classes columns
        else {
            $classParsed = array();
            foreach ($this->_entities as $entity) {
                if (!in_array(($class = get_class($entity)), $classParsed)) {
                    $classParsed[] = $class;
                    $columns       = $reader->extractColumnsFromEntity($entity);
                    $this->addColumns($columns);
                }
            }
        }
    }

    /**
     * @return bool
     */
    protected function _entitiesAreInstanceOfSameClass()
    {
        if ($this->_entitiesHaveSameClass !== null) return $this->_entitiesHaveSameClass;
        $class = get_class($this->_entities[0]);
        for ($i = 1; $i < count($this->_entities); $i++) {
            if ($class != get_class($this->_entities[$i]))
                return $this->_entitiesHaveSameClass = false;
        }

        return $this->_entitiesHaveSameClass = true;
    }

    /**
     * @return bool
     */
    protected function _hasCommonParent()
    {
        $higherParent = $this->_getObjectHigherParent($this->_entities[0]);
        for ($i = 1; $i < count($this->_entities); $i++) {
            if ($higherParent != $this->_getObjectHigherParent($this->_entities[$i]))
                return false;
        }

        return true;
    }

    /**
     * @param $object
     *
     * @return string The classname of the higher parent for this object
     */
    protected function _getObjectHigherParent($object)
    {
        for ($higher = ($class = get_class($object)); $class = get_parent_class($class); $higher = $class) ;

        return $higher;
    }

    /**
     * @return bool
     */
    protected function _isAllEntitiesObjects()
    {
        foreach ($this->_entities as $entity) if (!is_object($entity)) return false;

        return true;
    }
}