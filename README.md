# EntityPortationBundle

This is a bundle that allows you to simply export entities arrays using PHPExcel.

Simply place annotations on the elements of a class you want portable and the EntityPortationBundle will do the rest!
Pass it the list of entities to export and enjoy!

For now, only basic export is available but reading CSV/Excel files and creating entities from it is planned.

Please note that this bundle is still in development and may have some bugs or weird behaviors. Do not hesitate to send your feedbacks!

## License

[![License](https://poser.pugx.org/sostheng/entity-portation-bundle/license)](https://github.com/SosthenG/EntityPortationBundle/blob/master/LICENSE)

## Installation

**1**  Add to composer.json

``` shell
    $composer require sostheng/entity-portation-bundle
``` 

**2** Register the bundle in ``app/AppKernel.php``

``` php
    $bundles = array(
        // ...
        new SosthenG\EntityPortationBundle\EntityPortationBundle(),
    );
```

## Usage

This bundle uses annotations to detect what it needs to export. To use annotations, you must to include these two classes :

``` php
use SosthenG\EntityPortationBundle\Annotation\EntityPortation; // To pass custom parameters for the export
use SosthenG\EntityPortationBundle\Annotation\PortationGetter; // Required to tell which getter will be portable
```

Here is an example with all the parameters filled :

``` php
/**
 * @EntityPortation(csvDelimiter=";", sheetTitle="My sheet Title", fallBackValue="N/A")
 */
 class MyClass { 
    private $field;
 
    /**
     * @PortationGetter(label="Rôles", position="auto", visible=true, valueType="string")
     */
    public function getField()
    {
        return $this->_field;
    }
 }
```

They are all optionnal, the only required thing is to use the @PortationGetter() annotation on the getters you want to use for exports.

You've done the hardest! Now, just create an Export object, add extra parameters or change somes if you want, and get you exported file!

``` php
    /**
     * @Route("export/{format}", name="export", requirements={"format" = "PDF|Excel2007|Excel5|CSV|HTML|OpenDocument"})
     */
    public function exportAction($format) {
        $entities = $this->getDoctrine()->getManager()->getRepository("Bundle:MyClass")->findAll();

        $exporter = new Export($this->get("phpexcel"), $this->get("translator"));

        $exporter->setEntities($entities);
        
        // You can save the export as file or get a response from now, but if you want, you can change some parameters
        
        $prop = $exporter->getProperties(); // Returns the PhpExcel Properties object.
        $prop->setAuthor("You"); // Check the PHPExcel documentation for other parameters
        
        $exporter->setAllVisible(true);
        // etc.
        
        // Save the file
        $file = $exporter->saveAsFile($format, "web/files/myFileName"); // Extension is optionnal, it will be added if not filled
        // or return it as a Response
        return $exporter->getResponse($format, "myFileName");
    }
```

## Some documentation

This bundle uses the [ExcelBundle](https://github.com/liuggio/ExcelBundle/) from liuggio, which is a [PhpExcel](https://github.com/PHPOffice/PHPExcel/) integration for Symfony.

## Documentation for this bundle

Not yet available. Let me finish it first :)
