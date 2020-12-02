<?php

namespace Test;

use Ark4ne\XlReader\Factory;
use Ark4ne\XlReader\Reader\CsvReader;
use Ark4ne\XlReader\Reader\XlsxReader;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    public function testCreateReaderWithXlsx()
    {
        $base_fixture = __DIR__ . '/../fixture/';

        $reader = Factory::createReader($base_fixture . 'sheet-via-excel.xlsx');

        $this->assertInstanceOf(XlsxReader::class, $reader);

        $reader = Factory::createReader($base_fixture . 'sheet-via-numbers.xlsx');

        $this->assertInstanceOf(XlsxReader::class, $reader);
    }

    public function testCreateReaderWithTsv()
    {
        $base_fixture = __DIR__ . '/../fixture/';

        $reader = Factory::createReader($base_fixture . 'sheet.tsv');

        $this->assertInstanceOf(CsvReader::class, $reader);

        $this->assertEquals("\t", Helper::getPropertyValue($reader, 'delimiter'));
    }

    public function testCreateReaderWithCsvComma()
    {
        $base_fixture = __DIR__ . '/../fixture/';

        $reader = Factory::createReader($base_fixture . 'sheet-comma.csv');

        $this->assertInstanceOf(CsvReader::class, $reader);

        $this->assertEquals(",", Helper::getPropertyValue($reader, 'delimiter'));
    }

    public function testCreateReaderWithCsvSemicolon()
    {
        $base_fixture = __DIR__ . '/../fixture/';

        $reader = Factory::createReader($base_fixture . 'sheet-semicolon.csv');

        $this->assertInstanceOf(CsvReader::class, $reader);

        $this->assertEquals(";", Helper::getPropertyValue($reader, 'delimiter'));
    }
}
