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
     * @var array
     */
    public static $replaceablePrefixes = array('get', 'is', 'has', 'my');

    public static function getReplaceablePrefixes()
    {
        return self::$replaceablePrefixes;
    }

    /**
     * @var string
     */
    protected $_sheetTitle = '';

    /**
     * @var bool|null
     */
    protected $_entitiesHaveSameClass = null;

    /**
     * @var bool
     */
    protected $_replaceIfExists = true;

    /**
     * Be careful, if you prefer to let the PortationBundle detect if you entities are instances of the same class,
     * it will parse all of them to check if they are. It can increase the export time if you have a lot of entities.
     *
     * @param object[] $entities The entity array
     * @param null|bool $sameClass Are entities instance of the exact same class ?
     * @param bool $replaceIfExists If the reader find two or more columns with same name, should it replace with the last or keep the first ?
     *
     * @throws \OutOfBoundsException
     */
    public function setEntities(array $entities, $sameClass = null, $replaceIfExists = true)
    {
        if (count($entities) < 1)
            throw new \InvalidArgumentException("The entity array must not be empty.");

        if (!$this->_hasCommonParent($entities))
            throw new \InvalidArgumentException("Entities must have a common parent.");

        $this->_entities = $entities;
        $this->_replaceIfExists = $replaceIfExists;
        $this->_entitiesHaveSameClass = $sameClass;

        $this->_setPropertiesFromEntitiesAnnotation($this->_entities[0]);

        $this->_setColumnsFromReader();

        if (count($this->_columns) < 1)
            throw new \InvalidArgumentException("Entities have no accessible parameters.");

        $this->_phpExcelObject = $this->_phpExcelFactory->createPHPExcelObject();
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
    public function getResponse($outputType, $output, $encoding = "UTF8")
    {
        $this->_createPHPExcelObject();

        // If there is no extension, add the good one
        if (preg_match('/\.[a-zA-Z]+$/', $output) === 0) {
            $output .= '.'.$this->_getExtension($outputType);
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
            $output .= '.'.$this->_getExtension($outputType);
        }

        $writer = $this->_phpExcelFactory->createWriter($this->_phpExcelObject, $outputType);

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
    public function getSheetTitle()
    {
        return $this->_sheetTitle;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setSheetTitle($title)
    {
        $this->_sheetTitle = $title;

        return $this;
    }

    /**
     * @param $outputType
     *
     * @return string The extension that match the type or an empty string
     */
    protected function _getExtension($outputType)
    {
        $extensions = self::$extensions;

        if (empty($extensions[$outputType]))
            throw new \InvalidArgumentException("This filename has no extension and the output type is invalid.");

        return $extensions[$outputType];
    }

    /**
     * Generate the PHPExcel for the given entities
     * Automatically called when getResponse or saveAsfile are called.
     * Translate the columns names, if a translation exists
     */
    protected function _createPHPExcelObject()
    {
        // Deletes the sheet if it has already been defined
        $this->_phpExcelObject->disconnectWorksheets();
        $this->_phpExcelObject->createSheet();

        $sheet   = $this->_phpExcelObject->setActiveSheetIndex(0);
        $linePos = '1';

        $autoColumns = array();
        foreach ($this->_columns as $column => $options) {
            if ($options['position'] == 'auto') {
                $autoColumns[$column] = $options;
            }
            elseif ($options['visible']) {
                $label = !empty($options['label']) ? $this->_translator->trans($options['label']) : $column;

                $columnPos = \PHPExcel_Cell::stringFromColumnIndex($options['position']);
                if (!empty($sheet->getCell($columnPos . $linePos)->getValue()))
                    throw new \OutOfBoundsException("There is a position conflict, two columns asked for the same index.");

                $this->_columns[$column]['cell'] = $columnPos;

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

                $this->_columns[$column]['cell'] = $columnPos;

                $sheet->setCellValue($columnPos . $linePos, $label);
            }
        }

        foreach ($this->_entities as $entity) {
            $linePos++;
            foreach ($this->_columns as $columnName => $options) {
                if ($options['visible']) {
                    $getter = $options['getter'];
                    $value  = $this->_fallbackValue;

                    if (is_callable(array($entity, $getter)))
                        $value = $this->_convertValue($entity->$getter());
                    elseif (!empty($entity->$columnName))
                        $value = $this->_convertValue($entity->$columnName);
                    elseif ($result = $this->_checkPossibleGetters(new \ReflectionObject($entity), $columnName, $entity))
                        $value = $this->_convertValue($result);

                    $sheet->setCellValue($options['cell'] . $linePos, $value);
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
     * Recursive method that convert a given entity property value to string
     *
     * If the value is an object, the __toString method will be called if defined, or the object will be converted to array =>
     * If the value is an array, implode it to a string with a comma separator
     *
     * @param mixed   $value
     *
     * @return string
     */
    protected function _convertValue($value)
    {
        while (is_object($value)) {
            $refl = new \ReflectionObject($value);
            if ($refl->hasMethod('__toString')) $value = $refl->getMethod('__toString')->invoke($value);
            else $value = (array)$value;
        }

        if (is_array($value)) {
            $value = array_map(array($this, '_convertValue'), $value);
            $value = implode(', ', $value);
        }

        return $value;
    }

    /**
     * @param \ReflectionObject $refl
     * @param string            $property
     * @param object             $object
     *
     * @return mixed
     */
    protected function _checkPossibleGetters(\ReflectionObject $refl, $property, $object)
    {
        foreach (self::$replaceablePrefixes as $prefix) {
            if ($refl->hasMethod($prefix.ucfirst($property)) && $refl->getMethod($prefix.ucfirst($property))->isPublic()) {
                return $refl->getMethod($prefix.ucfirst($property))->invoke($object);
            }
        }
        return null;
    }

    /**
     * Detect the entities possible columns
     * In case of different objects (but inheriting from the same class), properties of all the objects will be used
     *
     * @throws \OutOfBoundsException
     */
    protected function _setColumnsFromReader()
    {
        $reader = new EntityPortationReader(new AnnotationReader());

        // If all entities are from the exact same class, just add the columns of the first one which are the sames for the others
        if ($this->_entitiesAreInstanceOfSameClass()) {
            $columns = $reader->extractColumnsFromEntity($this->_entities[0], $this->_replaceIfExists, $this->_annotate);
            $this->_addColumns($columns);
        }
        // Else, merge all classes columns
        else {
            $classParsed = array();
            foreach ($this->_entities as $entity) {
                if (!in_array(($class = get_class($entity)), $classParsed)) {
                    $classParsed[] = $class;
                    $columns       = $reader->extractColumnsFromEntity($entity, $this->_replaceIfExists, $this->_annotate);
                    $this->_addColumns($columns);
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
     * @param array $entities
     *
     * @return bool
     */
    protected function _hasCommonParent(array $entities)
    {
        $higherParent = $this->_getObjectHigherParent($entities[0]);
        for ($i = 1; $i < count($entities); $i++) {
            if ($higherParent != $this->_getObjectHigherParent($entities[$i]))
                return false;
        }

        return true;
    }

    /**
     * @param object $object
     *
     * @return string The classname of the higher parent for this object
     */
    protected function _getObjectHigherParent($object)
    {
        for ($higher = ($class = get_class($object)); $class = get_parent_class($class); $higher = $class) ;

        return $higher;
    }
}