<?php

namespace Ark4ne\XlReader\Reader;

use Ark4ne\XlReader\Exception\ReaderException;
use BadMethodCallException;
use Generator;

/**
 * Class DsvReader, Delimiter Separated Values Reader
 *
 * @package Ark4ne\XlReader\Reader
 */
class CsvReader implements IReader
{
    /**
     * @var string
     */
    private $file;
    /**
     * @var string
     */
    private $delimiter;
    /**
     * @var string
     */
    private $enclosure;
    /**
     * @var string
     */
    private $escape;

    /**
     * DsvReader constructor.
     *
     * @param string $file
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     */
    public function __construct(string $file, string $delimiter = ',', string $enclosure = '"', string $escape = '\\')
    {
        $this->file = $file;

        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;
    }

    /**
     * @param string $delimiter
     */
    public function setDelimiter(string $delimiter)
    {
        $this->delimiter = $delimiter;
    }

    /**
     * @param string $enclosure
     */
    public function setEnclosure(string $enclosure)
    {
        $this->enclosure = $enclosure;
    }

    /**
     * @param string $escape
     */
    public function setEscape(string $escape)
    {
        $this->escape = $escape;
    }

    /**
     * @throws \Ark4ne\XlReader\Exception\ReaderException
     */
    public function load()
    {
        if (!file_exists($this->file)) {
            throw new ReaderException("File {$this->file} doesn't exists.");
        }

        if (!is_readable($this->file)) {
            throw new ReaderException("File {$this->file} isn't readable.");
        }
    }

    /**
     * @param int      $start
     * @param int|null $end
     *
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

        try {
            $ptr = fopen($this->file, 'rb');

            $row_index = $start;

            // skip lines until reach $start_row;
            while (--$start && fgets($ptr)) ;

            do {
                $row = fgetcsv($ptr, 0, $this->delimiter, $this->enclosure, $this->escape);

                if ($row) {
                    yield $row_index++ => $this->readRow($row);
                }
            } while ($end >= $row_index && $row);
        } finally {
            fclose($ptr);
        }
    }

    private function readRow(array $row): array
    {
        $data = [];

        foreach ($row as $idx => $value) {
            $data[self::convertColumnIndexToString($idx)] = $value;
        }

        return $data;
    }

    private static $column_string_cache = [];

    private static function convertColumnIndexToString(int $index)
    {
        if (isset(self::$column_string_cache[$index])) {
            return self::$column_string_cache[$index];
        }

        for ($str = ""; $index >= 0; $index = (int)($index / 26) - 1) {
            $str = chr($index % 26 + 0x41) . $str;
        }

        return self::$column_string_cache[$index] = $str;
    }
}
