<?php
namespace SosthenG\EntityPortationBundle;

use Doctrine\Common\Annotations\AnnotationReader;
use Prophecy\Exception\Doubler\MethodNotFoundException;
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
    public static $replacablePrefix = array('get', 'is', 'has', 'my');

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

        $this->_setExportPropertiesFromEntitiesAnnotation();

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
     */
    protected function _setExportPropertiesFromEntitiesAnnotation()
    {
        $reader = new EntityPortationReader(new AnnotationReader());

        $properties = $reader->extractEntityParameters($this->_entities[0]);

        if (!empty($properties->sheetTitle)) $this->_sheetTitle = $properties->sheetTitle;
        if (!empty($properties->fallBackValue)) $this->_fallbackValue = $properties->fallBackValue;
        if (!empty($properties->csvDelimiter)) $this->_csvDelimiter = $properties->csvDelimiter;

        $this->_annotate = !empty($properties);
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
                    $value  = $this->_translator->trans($this->_fallbackValue);

                    if (is_callable(array($entity, $getter)))
                        $value = $this->_convertValue($entity->$getter(), $options);
                    elseif (!empty($entity->$columnName))
                        $value = $this->_convertValue($entity->$columnName, $options);
                    elseif ($result = $this->_checkPossibleGetters(new \ReflectionObject($entity), $columnName, $entity))
                        $value = $this->_convertValue($result, $options);

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
     *
     * @throws MethodNotFoundException
     */
    protected function _convertValue($value, array $options = array())
    {
        while (is_object($value)) {
            $refl = new \ReflectionObject($value);
            if (!empty($options['objectProperty'])) {
                if ($refl->getProperty($options['objectProperty'])->isPublic()) {
                    $value = $refl->getProperty($options['objectProperty'])->getValue();
                }
                else {
                    $value = $this->_checkPossibleGetters($refl, $options['objectProperty'], $value);
                    if ($value === null)
                        throw new \InvalidArgumentException("No getter was found in the class '".$refl->getName()."' for the property '".$options['objectProperty']."'");
                }
            }
            else {
                if ($refl->hasMethod('__toString')) $value = $refl->getMethod('__toString')->invoke($value);
                else $value = (array)$value;
            }
        }

        // TODO : Gérer les tableaux d'objets, eventuellement via une annotation qui spécifie quel getter utiliser

        if (is_array($value)) {
            $value = array_map(array($this, '_convertValue'), $value);
            $value = implode(', ', $value);
        }

        if (!empty($options)) {
            if ($options['valueType'] == 'boolean') {
                $value = $value ? $this->_translator->trans($this->_booleanValues[1]) : $this->_translator->trans($this->_booleanValues[0]);
            }
        }

        if (!empty($options) && $options['valueType'] == 'date') {
            $value = date($options['dateFormat'], strtotime($value));
        }
        elseif (is_string($value)) {
            $value = $this->_translator->trans($value);
        }

        return $value;
    }

    /**
     * @param \ReflectionObject $refl
     * @param string            $property
     * @param object             $object
     *
     * @return mixed
     *
     * @throws MethodNotFoundException
     */
    protected function _checkPossibleGetters(\ReflectionObject $refl, $property, $object)
    {
        foreach (self::$replacablePrefix as $prefix) {
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
        $reader = new EntityPortationReader(new AnnotationReader(), 'EXPORT');

        // If all entities are from the exact same class, just add the columns of the first one which are the sames for the others
        if ($this->_entitiesAreInstanceOfSameClass()) {
            $columns = $reader->extractColumnsFromEntity($this->_entities[0], $this->_replaceIfExists, $this->_annotate, 'EXPORT');
            $this->addColumns($columns);
        }
        // Else, merge all classes columns
        else {
            $classParsed = array();
            foreach ($this->_entities as $entity) {
                if (!in_array(($class = get_class($entity)), $classParsed)) {
                    $classParsed[] = $class;
                    $columns       = $reader->extractColumnsFromEntity($entity, $this->_replaceIfExists, $this->_annotate, 'EXPORT');
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