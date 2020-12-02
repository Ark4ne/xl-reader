<?php

namespace Test\Reader;

use Ark4ne\XlReader\Exception\ReaderException;
use Ark4ne\XlReader\Reader\XlsxReader;
use BadMethodCallException;
use PHPUnit\Framework\TestCase;
use Test\Helper;

class XlsxReaderTest extends TestCase
{
    public function dataFiles()
    {
        return [
            'sheet-via-excel.xlsx' => ['sheet-via-excel.xlsx'],
            'sheet-via-numbers.xlsx' => ['sheet-via-numbers.xlsx']
        ];
    }

    private function getReader($file)
    {
        $base_fixture = __DIR__ . '/../../fixture/';

        return new XlsxReader($base_fixture . $file);
    }

    /**
     * @dataProvider dataFiles
     */
    public function testSharedString($file)
    {
        $reader = $this->getReader($file);

        $reader->load();

        $shared = Helper::getPropertyValue($reader, 'shared');

        $this->assertEquals([
            'test',
            'abc',
            'def',
            'ghi'
        ], $shared);
    }

    /**
     * @dataProvider dataFiles
     */
    public function testWorksheets($file)
    {
        $reader = $this->getReader($file);

        $reader->load();

        $worksheets = $reader->getWorksheets();

        $this->assertEquals([
            ['id' => 1, 'name' => 'Feuil1']
        ], $worksheets);
    }

    /**
     * @dataProvider dataFiles
     */
    public function testSelectDefaultWorksheet($file)
    {
        $reader = $this->getReader($file);

        $reader->load();

        $worksheet = $reader->getSelectedWorksheet();

        $this->assertEquals([
            'id' => 1,
            'name' => 'Feuil1'
        ], $worksheet);
    }

    /**
     * @dataProvider dataFiles
     */
    public function testSelectWorksheetByIndex($file)
    {
        $reader = $this->getReader($file);

        $reader->load();

        $reader->selectSheetByIndex(0);

        $this->assertEquals([
            'id' => 1,
            'name' => 'Feuil1'
        ], $reader->getSelectedWorksheet());

        $this->expectException(ReaderException::class);
        $reader->selectSheetByIndex(1);
    }

    /**
     * @dataProvider dataFiles
     */
    public function testSelectWorksheetById($file)
    {
        $reader = $this->getReader($file);

        $reader->load();

        $reader->selectSheetById(1);

        $this->assertEquals([
            'id' => 1,
            'name' => 'Feuil1'
        ], $reader->getSelectedWorksheet());

        $this->expectException(ReaderException::class);
        $reader->selectSheetById(0);
    }

    /**
     * @dataProvider dataFiles
     */
    public function testSelectWorksheetByName($file)
    {
        $reader = $this->getReader($file);

        $reader->load();

        $reader->selectSheetByName('Feuil1');

        $this->assertEquals([
            'id' => 1,
            'name' => 'Feuil1'
        ], $reader->getSelectedWorksheet());

        $this->expectException(ReaderException::class);
        $reader->selectSheetByName('Feuil2');
    }

    /**
     * @dataProvider dataFiles
     */
    public function testReadFile($file)
    {
        $reader = $this->getReader($file);

        $reader->load();

        foreach ($reader->read() as $idx => $row) {
            $rows[$idx] = $row;
        }

        $this->assertEquals([
            'B' => 'test',
            'C' => 'abc',
            'E' => 'def',
        ], array_filter($rows[3]));

        $this->assertEquals([
            'B' => 123,
            'C' => '2020-12-01 00:00:00',
        ], array_filter($rows[4]));

        $this->assertEquals([
            'B' => 'ghi',
            'C' => 123.456,
            'E' => 'test',
        ], array_filter($rows[6]));
    }

    /**
     * @dataProvider dataFiles
     */
    public function testReadFileWithStartRow($file)
    {
        $reader = $this->getReader($file);

        $reader->load();

        foreach ($reader->read(4, 4) as $idx => $row) {
            $this->assertEquals(4, $idx);

            $rows[$idx] = $row;
        }

        $this->assertEquals([
            'B' => 123,
            'C' => '2020-12-01 00:00:00',
        ], array_filter($rows[4]));
    }

    public function testWrongStartRow()
    {
        $this->expectException(BadMethodCallException::class);
        $reader = $this->getReader('sheet-via-excel.xlsx');

        foreach ($reader->read(-1) as $value) {
        }
    }

    public function testWrongEnd()
    {
        $this->expectException(BadMethodCallException::class);
        $reader = $this->getReader('sheet-via-excel.xlsx');

        foreach ($reader->read(1, 0) as $value) {
        }
    }
}
