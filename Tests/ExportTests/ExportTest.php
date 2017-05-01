<?php

namespace SosthenG\EntityPortationBundle\Tests\ExportTests;

use SosthenG\EntityPortationBundle\Export;
use SosthenG\EntityPortationBundle\Tests\Entity\ChildEntityATestClass;
use SosthenG\EntityPortationBundle\Tests\Entity\ChildEntityBTestClass;
use SosthenG\EntityPortationBundle\Tests\Entity\OtherEntityTestClass;
use SosthenG\EntityPortationBundle\Tests\Entity\ParentEntityTestClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class ConverterTest
 *
 * @package SosthenG\EntityPortationBundle\Tests
 * @author  Sosthèn Gaillard <sosthen.gaillard@gmail.com>
 */
class ConverterTest extends KernelTestCase
{
    public $phpexcel   = null;

    public $translator = null;

    public $writeDir   = 'var/cache/test/export_test';

    public function setUp()
    {
        self::bootKernel();
        $this->phpexcel   = static::$kernel->getContainer()
                                           ->get('phpexcel');
        $this->translator = static::$kernel->getContainer()
                                           ->get('translator');
    }

    public function testFullAnnotationExport()
    {
        $this->clearWriteDir();

        $user = "Tom";

        $entities   = array();
        $entities[] = new ParentEntityTestClass(1, "James", "Doe", 54);
        $entities[] = new ChildEntityATestClass(2, "John", "Doe", 25, true, "22 Baker street");
        $entities[] = new ChildEntityBTestClass(3, "Jane", "Doe", 33, '0101010101');

        $entityExport = new Export($this->phpexcel, $this->translator);
        $entityExport->setEntities($entities);

        $columns = array('id', 'firstname', 'lastname', 'adress', 'phonenumber');
        foreach ($columns as $column) {
            $this->assertArrayHasKey($column, $entityExport->getColumns());
        }

        $entityExport->setBooleanValues(array("nope", "yep"));
        $entityExport->setSheetTitle("Sheet for " . $user);
        $entityExport->getProperties()
                     ->setCreator($user)
                     ->setLastModifiedBy($user)
                     ->setTitle("Export for " . $user)
                     ->setDescription("This is an export of somes entities for the user " . $user)
                     ->setKeywords("export test entity")
                     ->setCategory("Test result file")
                     ->setCompany("SosthenG");

        // Test Response export
        $response = $entityExport->getResponse('CSV', 'myResponseFile');

        $this->assertInstanceOf(StreamedResponse::class, $response);

        // Test saveAsFile export
        $entityExport->saveAsfile('CSV', $this->writeDir . '/myfile.csv');
        $entityExport->saveAsfile('CSV', $this->writeDir . '/myfile_without_ext');
        $entityExport->saveAsfile('Excel2007', $this->writeDir . '/excel_file');

        $this->assertFileIsReadable($this->writeDir . '/myfile.csv');
        $this->assertFileIsReadable($this->writeDir . '/myfile_without_ext.csv');
        $this->assertFileEquals($this->writeDir . '/myfile.csv', $this->writeDir . '/myfile_without_ext.csv');
    }

    public function testManualOptions()
    {
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testEmptyEntities()
    {
        $entities = array();

        $entityExport = new Export($this->phpexcel, $this->translator);
        $entityExport->setEntities($entities);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNoCommonParent()
    {
        $entities   = array();
        $entities[] = new ChildEntityATestClass(1, "John", "Doe", 25, true, "22 Baker street");
        $entities[] = new OtherEntityTestClass("blob");

        $entityExport = new Export($this->phpexcel, $this->translator);
        $entityExport->setEntities($entities);
    }

    private function clearWriteDir()
    {
        if (is_dir($this->writeDir)) {
            foreach (glob($this->writeDir . '/*') as $file) unlink($file);
        }
        else {
            mkdir($this->writeDir);
        }
    }
}