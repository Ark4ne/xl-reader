<?php


namespace Ark4ne\XlReader;


use Ark4ne\XlReader\Exception\ReaderException;
use Ark4ne\XlReader\Exception\UnsupportedFileFormat;
use Ark4ne\XlReader\Reader\CsvReader;
use Ark4ne\XlReader\Reader\IReader;
use Ark4ne\XlReader\Reader\XlsxReader;

class Factory
{
    /**
     * @param string $file
     *
     * @throws \Ark4ne\XlReader\Exception\UnsupportedFileFormat
     * @throws \Ark4ne\XlReader\Exception\ReaderException
     * @return \Ark4ne\XlReader\Reader\IReader
     */
    public static function createReader(string $file): IReader
    {
        $ext = substr($file, strrpos($file, '.') + 1);

        switch ($ext) {
            case 'xlsx':
            case 'xlsm':
            case 'xltx':
            case 'xltm':
                return new XlsxReader($file);
            case 'tsv':
                return new CsvReader($file, "\t");
            case 'csv':
                if (($reader = self::detectCommaOrSemicolon($file))) {
                    return $reader;
                }
        }

        throw new UnsupportedFileFormat();
    }

    /**
     * @param string $file
     *
     * @throws \Ark4ne\XlReader\Exception\ReaderException
     */
    private static function detectCommaOrSemicolon(string $file): CsvReader
    {
        if (!file_exists($file)) {
            throw new ReaderException("File {$file} doesn't exists.");
        }

        if (!is_readable($file)) {
            throw new ReaderException("File {$file} isn't readable.");
        }

        try {
            $ptr = fopen($file, 'rb');

            $caracts = str_split(fgets($ptr));
            $caracts_count = count($caracts);

            for ($i = 0; $i < $caracts_count; $i++) {
                $last = $caracts[$i - 1] ?? null;
                $caract = $caracts[$i];

                if ($caract === ',' && $last !== '\\') {
                    return new CsvReader($file);
                }
                if ($caract === ';' && $last !== '\\') {
                    return new CsvReader($file, ';');
                }
                // if $caract = '"' it's a enclosure, move cursor next end enclosure.
                if ($caract === '"' && $last !== '\\') {
                    while ($caracts[++$i] !== '"' && $caracts[$i - 1] !== '\\' && $i < $caracts_count) ;
                }
            }

            return new CsvReader($file);
        } finally {
            fclose($ptr);
        }
    }
}
