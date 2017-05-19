<?php

namespace SosthenG\EntityPortationBundle\Tests\ExportTests;

use SosthenG\EntityPortationBundle\Export;
use SosthenG\EntityPortationBundle\Import;
use SosthenG\EntityPortationBundle\Tests\Entity\ChildEntityATestClass;
use SosthenG\EntityPortationBundle\Tests\Entity\ChildEntityBTestClass;
use SosthenG\EntityPortationBundle\Tests\Entity\IncludedObject;
use SosthenG\EntityPortationBundle\Tests\Entity\OtherEntityTestClass;
use SosthenG\EntityPortationBundle\Tests\Entity\ParentEntityTestClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class ImportTest
 *
 * @package SosthenG\EntityPortationBundle\Tests
 * @author  Sosthèn Gaillard <sosthen.gaillard@gmail.com>
 */
class ImportTest extends KernelTestCase
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

    public function testImport()
    {
        $entities   = array();
        $entities[] = new ParentEntityTestClass(1, "James", "Doe", 54);
        $entities[] = new ParentEntityTestClass(2, "John", "Doe", 25);
        $entities[] = new ParentEntityTestClass(3, "Jane", "Doe", 33);

        $entityExport = new Export($this->phpexcel, $this->translator);
        $entityExport->setEntities($entities);
        $entityExport->saveAsfile('CSV', $this->writeDir . '/import_test.csv');

        $entityImport = new Import($this->phpexcel, $this->translator);
        $importedEntities = $entityImport->createEntitesFromFile($this->writeDir . '/import_test.csv', ParentEntityTestClass::class);

        foreach ($importedEntities as $key => $entity) {
            $this->assertEquals($entities[$key], $entity);
        }

        // Check that there is no not found values
        $this->assertEquals(array(), $entityImport->getNotFoundValues());
    }
}