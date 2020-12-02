<?php

namespace Test\Reader;

use Ark4ne\XlReader\Reader\CsvReader;
use BadMethodCallException;
use PHPUnit\Framework\TestCase;

class CsvReaderTest extends TestCase
{
    public function dataFiles()
    {
        return [
            'sheet.tsv' => ['sheet.tsv', "\t", '"', '\\'],
            'sheet-comma.csv' => ['sheet-comma.csv', ",", '"', '\\'],
            'sheet-semicolon.csv' => ['sheet-semicolon.csv', ";", '"', '\\'],
        ];
    }

    private function getReader($file, $delimiter, $enclosure, $escape)
    {
        $base_fixture = __DIR__ . '/../../fixture/';

        $reader = new CsvReader($base_fixture . $file);

        $reader->setDelimiter($delimiter);
        $reader->setEnclosure($enclosure);
        $reader->setEscape($escape);

        return $reader;
    }

    /**
     * @dataProvider dataFiles
     */
    public function testReadFile($file, $delimiter, $enclosure, $escape)
    {
        $reader = $this->getReader($file, $delimiter, $enclosure, $escape);

        $reader->load();

        foreach ($reader->read() as $idx => $row) {
            $rows[$idx] = $row;
        }

        $this->assertCount(6, $rows);

        $rows = array_filter(array_map('array_filter', $rows));

        $this->assertEquals([
            'B' => 'test',
            'C' => 'abc',
            'E' => 'def',
        ], array_filter($rows[3]));

        $this->assertEquals([
            'B' => '123',
            'C' => '12/1/20',
        ], array_filter($rows[4]));

        $this->assertEquals([
            'B' => 'ghi',
            'C' => '123,456',
            'E' => 'test',
        ], array_filter($rows[6]));
    }

    /**
     * @dataProvider dataFiles
     */
    public function testReadFileWithStartRow($file, $delimiter, $enclosure, $escape)
    {
        $reader = $this->getReader($file, $delimiter, $enclosure, $escape);

        $reader->load();

        foreach ($reader->read(4, 4) as $idx => $row) {
            $this->assertEquals(4, $idx);

            $rows[$idx] = $row;
        }

        $this->assertEquals([
            'B' => '123',
            'C' => '12/1/20',
        ], array_filter($rows[4]));
    }

    public function testWrongStartRow()
    {
        $this->expectException(BadMethodCallException::class);
        $reader = new CsvReader('');

        foreach ($reader->read(-1) as $value) {
        }
    }

    public function testWrongEnd()
    {
        $this->expectException(BadMethodCallException::class);
        $reader = new CsvReader('');

        foreach ($reader->read(1, 0) as $value) {
        }
    }
}
