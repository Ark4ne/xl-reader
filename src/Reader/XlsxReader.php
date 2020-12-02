<?php

namespace Ark4ne\XlReader\Reader;

use Ark4ne\XlReader\Exception\ReaderException;
use Ark4ne\XlReader\Helper\XmlReader;
use BadMethodCallException;
use Generator;
use ZipArchive;

class XlsxReader implements IReader
{
    /** @var array */
    const XLSX_FORMATS = [
        0 => 'General',
        1 => '0',
        2 => '0.00',
        3 => '#,##0',
        4 => '#,##0.00',
        9 => '0%',
        10 => '0.00%',
        11 => '0.00E+00',
        12 => '# ?/?',
        13 => '# ??/??',
        14 => 'mm-dd-yy',
        15 => 'd-mmm-yy',
        16 => 'd-mmm',
        17 => 'mmm-yy',
        18 => 'h:mm AM/PM',
        19 => 'h:mm:ss AM/PM',
        20 => 'h:mm',
        21 => 'h:mm:ss',
        22 => 'm/d/yy h:mm',
        37 => '#,##0 ;(#,##0)',
        38 => '#,##0 ;[Red](#,##0)',
        39 => '#,##0.00;(#,##0.00)',
        40 => '#,##0.00;[Red](#,##0.00)',
        45 => 'mm:ss',
        46 => '[h]:mm:ss',
        47 => 'mmss.0',
        48 => '##0.0E+0',
        49 => '@',
    ];

    /** @var array */
    const DATE_TIME_CHARACTERS = ['e', 'd', 'h', 'm', 's', 'yy'];

    /** @var string */
    const TYPE_BOOLEAN = 'b';

    /** @var string */
    const TYPE_DATE = 'd';

    /** @var string */
    const TYPE_SHARED_STRING = 's';

    /** @var string */
    const TYPE_INLINE_STRING = 'inlineStr';

    /** @var string */
    protected $file;

    /** @var bool */
    protected $date1904 = false;

    /** @var array */
    protected $shared = [];

    /** @var array */
    protected $formats = [];

    /** @var array */
    protected $worksheets = [];

    /** @var array */
    protected $worksheet;

    /**
     * XLSXReader constructor.
     *
     * @param string $file
     */
    public function __construct(string $file)
    {
        $this->file = $file;
    }

    /**
     * Load metadata
     *
     * @throws \Ark4ne\XlReader\Exception\ReaderException
     */
    public function load()
    {
        $zip = new ZipArchive();

        if (!$zip->open($this->file)) {
            throw new ReaderException("Can't open file : {$this->file}");
        }

        if (false === $zip->locateName('xl/workbook.xml')) {
            throw new ReaderException('File not contains xL/workbook.xml');
        }

        $this->loadWorkbook();

        if (false !== $zip->locateName('xl/sharedStrings.xml')) {
            $this->loadSharedString();
        }

        if (false !== $zip->locateName('xl/styles.xml')) {
            $this->loadFormats();
        }

        $zip->close();
    }

    /**
     * Select a sheet by is id
     *
     * @param int $index
     *
     * @throws \Ark4ne\XlReader\Exception\ReaderException
     */
    public function selectSheetByIndex(int $index)
    {
        if (!isset($this->worksheets[$index])) {
            throw new ReaderException("Worksheet index: $index not found.");
        }

        $this->worksheet = $this->worksheets[$index];
    }

    /**
     * Select a sheet by is id
     *
     * @param int $id
     *
     * @throws \Ark4ne\XlReader\Exception\ReaderException
     */
    public function selectSheetById(int $id)
    {
        foreach ($this->worksheets as $worksheet) {
            if ($worksheet['id'] === $id) {
                $this->worksheet = $worksheet;

                return;
            }
        }

        throw new ReaderException("Worksheet id: $id not found.");
    }

    /**
     * Select a sheet by is name
     *
     * @param string $name
     *
     * @throws \Ark4ne\XlReader\Exception\ReaderException
     */
    public function selectSheetByName(string $name)
    {
        foreach ($this->worksheets as $worksheet) {
            if ($worksheet['name'] === $name) {
                $this->worksheet = $worksheet;

                return;
            }
        }

        throw new ReaderException("Worksheet name: $name not found.");
    }

    /**
     * Return all worksheets.
     *
     * @return array
     */
    public function getWorksheets(): array
    {
        return $this->worksheets;
    }

    /**
     * Return selected worksheet.
     *
     * @return array
     */
    public function getSelectedWorksheet(): array
    {
        if (!isset($this->worksheet['id'])) {
            $this->selectSheetByIndex(0);
        }

        return $this->worksheet;
    }

    /**
     * Read all row for selected worksheet
     *
     * @param int $start
     * @param int|null $end
     *
     * @throws \Ark4ne\XlReader\Exception\ReaderException
     * @return \Generator
     */
    public function read(int $start = 1, int $end = null): Generator
    {
        if ($start <= 0) {
            throw new BadMethodCallException('$start must be greater then 0.');
        }

        $end = $end ?? INF;

        if ($start > $end) {
            throw new BadMethodCallException('$start must be less then $end.');
        }

        if (!isset($this->worksheet['id'])) {
            $this->selectSheetByIndex(0);
        }

        $reader = $this->getXMLReader("xl/worksheets/sheet{$this->worksheet['id']}.xml");

        try {
            if (!$reader->find('row')) {
                throw new ReaderException("Can't find any row");
            }

            while ($start > (int)$reader->getAttribute('r')) {
                $reader->next('row');
            }

            do {
                $row_index = (int)$reader->getAttribute('r');

                if ($end < $row_index) {
                    break;
                }

                yield $row_index => $this->readRow($reader, (string)$row_index);
            } while ($end >= $row_index && $reader->next('row'));
        } finally {
            $reader->close();
        }
    }

    /**
     * Read a row.
     *
     * @param \Ark4ne\XlReader\Helper\XmlReader $reader
     * @param string                            $row_index
     *
     * @return array
     */
    protected function readRow(XmlReader $reader, string $row_index): array
    {
        if ($reader->isEmptyElement) {
            return [];
        }

        $row_index_length = strlen($row_index);

        $shared = $this->shared;
        $formats = $this->formats;

        $values = [];

        while ($reader->read()) {
            $node_name = $reader->name;
            $node_type = $reader->nodeType;

            if (XmlReader::END_ELEMENT === $node_type && 'row' === $node_name) {
                break;
            }

            if (XmlReader::ELEMENT !== $node_type) {
                continue;
            }

            if ('c' === $node_name) {
                $column = substr($reader->getAttribute('r'), 0, -$row_index_length);

                $type = $reader->getAttribute('t');

                if (null === $type) {
                    $style = $reader->getAttribute('s');
                    $type = $formats[$style]['type'] ?? null;
                }

                if ($reader->isEmptyElement) {
                    $node_value = null;
                } else {
                    $reader->find($type === self::TYPE_INLINE_STRING ? 't' : 'v');

                    $node_value = $reader->readValue();
                }

                if ($type === self::TYPE_INLINE_STRING) {
                    // inline string
                    $value = $node_value;
                } elseif ($type === self::TYPE_SHARED_STRING) {
                    // shared string
                    $value = $shared[$node_value];
                } elseif ($type === self::TYPE_DATE) {
                    // date
                    $value = $this->parseDate((float)$node_value);
                } elseif ($type === self::TYPE_BOOLEAN) {
                    // boolean
                    $value = (bool)$node_value;
                } else {
                    // string / number
                    $value = $node_value;

                    // Check for numeric values
                    if (is_numeric($value)) {
                        if (strpos($value, '.') === false) {
                            $value = (int)$value;
                        } else {
                            $value = (float)$value;
                        }
                    }
                }

                $values[$column] = $value;
            }
        }

        return $values;
    }

    /**
     * Parse date
     *
     * @param float $value
     *
     * @return false|string
     */
    protected function parseDate(float $value)
    {
        $d = floor($value); // days since 1900 or 1904
        $t = $value - $d;

        if ($this->date1904) {
            $d += 1462;
        }

        $t = (abs($d) > 0)
            ? ($d - 25569) * 86400 + round($t * 86400)
            : round($t * 86400);

        return date('Y-m-d H:i:s', $t);
    }

    /**
     * Load shared strings
     *
     * @throws ReaderException
     */
    protected function loadSharedString()
    {
        $reader = $this->getXMLReader('xl/sharedStrings.xml');

        while ($reader->find('t')) {
            $this->shared[] = $reader->readValue();
        }

        $reader->close();
    }

    /**
     * Load all cells formats
     *
     * @throws ReaderException
     */
    protected function loadFormats()
    {
        $in_cellxfs = false;
        $styles = self::XLSX_FORMATS;

        $reader = $this->getXMLReader('xl/styles.xml');

        while ($reader->read()) {
            $node_name = $reader->name;
            $node_type = $reader->nodeType;

            if ('cellXfs' === $node_name) {
                if ($node_type === XmlReader::ELEMENT) {
                    $in_cellxfs = !$reader->isEmptyElement;
                } elseif ($node_type === XmlReader::END_ELEMENT) {
                    $in_cellxfs = false;
                }

                continue;
            }

            if ($node_type !== XmlReader::ELEMENT) {
                continue;
            }

            if ('numFmt' === $node_name) {
                $styles[$reader->getAttribute('numFmtId')] = $reader->getAttribute('formatCode');
            } elseif ('xf' === $node_name && $in_cellxfs) {
                $nfi = $reader->getAttribute('numFmtId');
                $style = $styles[$nfi] ?? null;
                $type = null;
                if ($nfi !== '0' && $style) {
                    $test = preg_replace('((?<!\\\)\[.+?(?<!\\\)])', '', $style);

                    foreach (self::DATE_TIME_CHARACTERS as $character) {
                        if (strpos($test, $character) !== false) {
                            $type = self::TYPE_DATE;
                            break;
                        }
                    }
                }

                $this->formats[] = [
                    'style' => $style,
                    'type' => $type,
                ];
            }
        }

        $reader->close();
    }

    /**
     * Load worksheets, and verify date format
     *
     * @throws ReaderException
     */
    protected function loadWorkbook()
    {
        $reader = $this->getXMLReader('xl/workbook.xml');

        while ($reader->read()) {
            if ($reader->nodeType !== XmlReader::ELEMENT) {
                continue;
            }

            $node_name = $reader->name;

            if ('date1904' === $node_name) {
                $value = trim($reader->readValue());

                $this->date1904 = $value === '1' || $value === 'true';
            } elseif ('sheet' === $node_name) {
                $this->worksheets[] = [
                    'name' => $reader->getAttribute('name'),
                    'id' => (int)$reader->getAttribute('sheetId'),
                ];
            }
        }

        $reader->close();
    }

    /**
     * Return XMLReader for part of xlsx file.
     *
     * @param string $part_path
     *
     * @throws \Ark4ne\XlReader\Exception\ReaderException
     * @return \Ark4ne\XlReader\Helper\XmlReader
     */
    protected function getXMLReader(string $part_path): XmlReader
    {
        $reader = new XmlReader();

        if (!$reader->open("zip://{$this->file}#$part_path")) {
            throw new ReaderException("Can't open part $part_path.");
        }

        return $reader;
    }
}
