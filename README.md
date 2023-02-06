# xl-reader
![Build Status](https://github.com/Ark4ne/laravel-json-api/actions/workflows/php.yml/badge.svg)
[![Coverage Status](https://codecov.io/gh/Ark4ne/xl-reader/branch/main/graph/badge.svg?token=SI50NJ20CE)](https://codecov.io/gh/Ark4ne/xl-reader)


High performance excel reader, with very low memory consumption.

# Installation
```
$ composer require ark4ne/xl-reader
```

# File support 

* `xlsx`: fastest xlsx reader ever.
* `tsv`: tsv reader.
* `csv`: configurable csv reader (auto detect comma, or semi) .

# Usage

### Read file
```php
$file = "my-calc.xlsx";

$reader = \Ark4ne\XlReader\Factory::createReader($file);

$reader->load();

foreach ($reader->read() as $row){
    // do stuff
}
```

Each `$row` will contains data indexed by column key (A, B, C, ...). 

```php
/*
my-calc.xlsx
| A     | B     | C    |
| abc   | 123   | some |
*/
foreach ($reader->read() as $row){
    $row === [
        'A' => 'abc',
        'B' => '123',
        'C' => 'some',
    ];
}
```

With excel empty cells will not be reported
```php
/*
my-calc.xlsx
| A     | B     | C    |
| abc   |       | some |
*/

foreach ($reader->read() as $row){
    // do stuff
    $row === [
        'A' => 'abc',
        'C' => 'some',
    ];
}
```

With numbers (mac), and many xlsx other generator, empty cells will be reported as `null`
```php
/*
my-calc.xlsx
| A     | B     | C    |
| abc   |       | some |
*/

foreach ($reader->read() as $row){
    // do stuff
    $row === [
        'A' => 'abc',
        'B' => null,
        'C' => 'some',
    ];
}
```

### Work with sheets (XLSX Reader)

By default, the first sheet is read. 

You can retrieve all worksheets with `getWorksheets()`.
```php
$worksheets = $reader->getWorksheets();
/*
[
    ['id' => 1, 'name' => 'sheet 1'],
    ['id' => 2, 'name' => 'sheet 2'],
]
*/
```

You can retrieve selected worksheet with `getSelectedWorksheet()`.
```php
$worksheet = $reader->getSelectedWorksheet();
/*
['id' => 1, 'name' => 'sheet 1'],
*/
```

You have three ways to select the sheets to work with:

* by index : `selectSheetByIndex(int $index)`
* by id : `selectSheetById(int $id)`
* by name : `selectSheetByName(string $name)`
 
# Performance

| legend     |                                                        |
|------------|--------------------------------------------------------|
| `mem`      | php memory usage with `memory_get_peak_usage()`        |
| `real mem` | process real mem usage with `/usr/bin/time -l php ...` |


##  Memory

The memory usage is affected only by the number of strings contains in the file to read :

| str     | size (Ko)   | load()     | mem       | real mem  |
|---------|-------------|------------|-----------|-----------|
| 1024    | 13Ko        | 3.819ms    | 0.811MB   | 12.188MB  |
| 32768   | 416Ko       | 43.899ms   | 2.900MB   | 14.273MB  |
| 262144  | 3.32Mo      | 363.536ms  | 18.650MB  | 30.246MB  |
| 524288  | 6.65Mo      | 689.840ms  | 36.650MB  | 48.281MB  |

`load()` method is directly affected by this. More strings they are to load, more time we need to load. (logic, anything is magical) 

Once the strings are loaded, we have reached the memory used peak.  

Reading the data consumes almost no memory.  
Only the current line will be loaded into memory.

##  Read

Benchmark with 1024 str : 

| rows x cols  | cells      | read          | mem       | real mem  |
|--------------|------------|---------------|-----------|-----------|
| 100 x 4      | 400        | 1.152ms       | 0.811MB   | 12.188MB  |
| 100 x 16     | 1 600      | 3.520ms       | 0.811MB   | 12.121MB  |
| 100 x 32     | 3 200      | 7.046ms       | 0.811MB   | 12.262MB  |
| 100 x 64     | 6 400      | 13.758ms      | 0.811MB   | 12.270MB  |
|              |            |               |           |           |
| 1000 x 4     | 4 000      | 9.382ms       | 0.811MB   | 12.203MB  |
| 1000 x 16    | 16 000     | 32.149ms      | 0.811MB   | 12.129MB  |
| 1000 x 32    | 32 000     | 65.125ms      | 0.811MB   | 12.203MB  |
| 1000 x 64    | 64 000     | 136.509ms     | 0.811MB   | 12.320MB  |
|              |            |               |           |           |
| 10000 x 4    | 40 000     | 97.383ms      | 0.811MB   | 12.121MB  |
| 10000 x 16   | 160 000    | 362.781ms     | 0.811MB   | 12.367MB  |
| 10000 x 32   | 320 000    | 656.291ms     | 0.811MB   | 12.383MB  |
| 10000 x 64   | 640 000    | 1 344.341ms   | 0.811MB   | 12.484MB  |
|              |            |               |           |           |
| 100000 x 4   | 400 000    | 920.734ms     | 0.811MB   | 12.559MB  |
| 100000 x 16  | 1 600 000  | 3 338.086ms   | 0.811MB   | 12.473MB  |
| 100000 x 32  | 3 200 000  | 6 636.691ms   | 0.811MB   | 12.488MB  |
| 100000 x 64  | 6 400 000  | 13 572.875ms  | 0.811MB   | 12.527MB  |

The reading speed depends on the number of cells to be read.
The more cells there are to read, the longer the reading time will be.

**However, the number of cells to be read has no effect on the memory used.**

##  All bench result

| rows x cols  | cells      | str     | load       | read          | mem       | real mem  |
|--------------|------------|---------|------------|---------------|-----------|-----------|
| 100 x 4      | 400        | 1024    | 3.819ms    | 1.152ms       | 0.811MB   | 12.188MB  |
| 100 x 4      | 400        | 32768   | 43.899ms   | 1.308ms       | 2.900MB   | 14.273MB  |
| 100 x 4      | 400        | 262144  | 363.536ms  | 1.216ms       | 18.650MB  | 30.246MB  |
| 100 x 4      | 400        | 524288  | 689.840ms  | 1.296ms       | 36.650MB  | 48.281MB  |
| 100 x 16     | 1 600      | 1024    | 2.535ms    | 3.520ms       | 0.811MB   | 12.121MB  |
| 100 x 16     | 1 600      | 32768   | 44.586ms   | 3.644ms       | 2.902MB   | 14.180MB  |
| 100 x 16     | 1 600      | 262144  | 347.970ms  | 3.939ms       | 18.652MB  | 30.156MB  |
| 100 x 16     | 1 600      | 524288  | 700.403ms  | 4.054ms       | 36.652MB  | 48.379MB  |
| 100 x 32     | 3 200      | 1024    | 2.917ms    | 7.046ms       | 0.811MB   | 12.262MB  |
| 100 x 32     | 3 200      | 32768   | 49.992ms   | 7.068ms       | 2.905MB   | 14.410MB  |
| 100 x 32     | 3 200      | 262144  | 358.629ms  | 7.810ms       | 18.655MB  | 30.160MB  |
| 100 x 32     | 3 200      | 524288  | 720.848ms  | 8.478ms       | 36.655MB  | 48.328MB  |
| 100 x 64     | 6 400      | 1024    | 3.175ms    | 13.758ms      | 0.811MB   | 12.270MB  |
| 100 x 64     | 6 400      | 32768   | 44.186ms   | 15.912ms      | 2.914MB   | 14.414MB  |
| 100 x 64     | 6 400      | 262144  | 358.572ms  | 14.553ms      | 18.664MB  | 30.273MB  |
| 100 x 64     | 6 400      | 524288  | 729.723ms  | 19.326ms      | 36.664MB  | 48.250MB  |
|              |            |         |            |               |           |           |
| 1000 x 4     | 4 000      | 1024    | 2.651ms    | 9.382ms       | 0.811MB   | 12.203MB  |
| 1000 x 4     | 4 000      | 32768   | 50.050ms   | 9.998ms       | 2.900MB   | 14.453MB  |
| 1000 x 4     | 4 000      | 262144  | 347.184ms  | 10.336ms      | 18.650MB  | 30.180MB  |
| 1000 x 4     | 4 000      | 524288  | 707.146ms  | 10.559ms      | 36.650MB  | 48.262MB  |
| 1000 x 16    | 16 000     | 1024    | 2.554ms    | 32.149ms      | 0.811MB   | 12.129MB  |
| 1000 x 16    | 16 000     | 32768   | 45.078ms   | 33.686ms      | 2.902MB   | 14.309MB  |
| 1000 x 16    | 16 000     | 262144  | 346.377ms  | 33.933ms      | 18.652MB  | 30.164MB  |
| 1000 x 16    | 16 000     | 524288  | 695.764ms  | 36.088ms      | 36.652MB  | 48.262MB  |
| 1000 x 32    | 32 000     | 1024    | 2.563ms    | 65.125ms      | 0.811MB   | 12.203MB  |
| 1000 x 32    | 32 000     | 32768   | 43.847ms   | 66.912ms      | 2.905MB   | 14.434MB  |
| 1000 x 32    | 32 000     | 262144  | 348.987ms  | 83.518ms      | 18.655MB  | 30.203MB  |
| 1000 x 32    | 32 000     | 524288  | 690.199ms  | 69.770ms      | 36.655MB  | 48.305MB  |
| 1000 x 64    | 64 000     | 1024    | 2.567ms    | 136.509ms     | 0.811MB   | 12.320MB  |
| 1000 x 64    | 64 000     | 32768   | 43.872ms   | 141.797ms     | 2.914MB   | 14.453MB  |
| 1000 x 64    | 64 000     | 262144  | 346.000ms  | 161.795ms     | 18.664MB  | 30.254MB  |
| 1000 x 64    | 64 000     | 524288  | 681.378ms  | 141.121ms     | 36.664MB  | 48.242MB  |
|              |            |         |            |               |           |           |
| 10000 x 4    | 40 000     | 1024    | 2.646ms    | 97.383ms      | 0.811MB   | 12.121MB  |
| 10000 x 4    | 40 000     | 32768   | 43.895ms   | 94.159ms      | 2.900MB   | 14.180MB  |
| 10000 x 4    | 40 000     | 262144  | 356.011ms  | 101.299ms     | 18.650MB  | 30.223MB  |
| 10000 x 4    | 40 000     | 524288  | 685.486ms  | 96.134ms      | 36.650MB  | 48.254MB  |
| 10000 x 16   | 160 000    | 1024    | 2.575ms    | 362.781ms     | 0.811MB   | 12.367MB  |
| 10000 x 16   | 160 000    | 32768   | 47.681ms   | 376.481ms     | 2.902MB   | 14.406MB  |
| 10000 x 16   | 160 000    | 262144  | 370.344ms  | 365.026ms     | 18.652MB  | 30.230MB  |
| 10000 x 16   | 160 000    | 524288  | 688.249ms  | 361.975ms     | 36.652MB  | 48.328MB  |
| 10000 x 32   | 320 000    | 1024    | 2.604ms    | 656.291ms     | 0.811MB   | 12.383MB  |
| 10000 x 32   | 320 000    | 32768   | 48.109ms   | 700.548ms     | 2.905MB   | 14.520MB  |
| 10000 x 32   | 320 000    | 262144  | 351.243ms  | 708.751ms     | 18.655MB  | 30.309MB  |
| 10000 x 32   | 320 000    | 524288  | 694.430ms  | 731.479ms     | 36.655MB  | 48.480MB  |
| 10000 x 64   | 640 000    | 1024    | 2.736ms    | 1 344.341ms   | 0.811MB   | 12.484MB  |
| 10000 x 64   | 640 000    | 32768   | 46.607ms   | 1 374.319ms   | 2.914MB   | 14.520MB  |
| 10000 x 64   | 640 000    | 262144  | 349.125ms  | 1 371.404ms   | 18.664MB  | 30.297MB  |
| 10000 x 64   | 640 000    | 524288  | 685.803ms  | 1 439.625ms   | 36.664MB  | 48.387MB  |
|              |            |         |            |               |           |           |
| 100000 x 4   | 400 000    | 1024    | 2.986ms    | 920.734ms     | 0.811MB   | 12.559MB  |
| 100000 x 4   | 400 000    | 32768   | 45.819ms   | 953.416ms     | 2.900MB   | 14.570MB  |
| 100000 x 4   | 400 000    | 262144  | 351.414ms  | 944.669ms     | 18.650MB  | 30.301MB  |
| 100000 x 4   | 400 000    | 524288  | 690.455ms  | 970.586ms     | 36.650MB  | 48.305MB  |
| 100000 x 16  | 1 600 000  | 1024    | 3.934ms    | 3 338.086ms   | 0.811MB   | 12.473MB  |
| 100000 x 16  | 1 600 000  | 32768   | 43.459ms   | 3 488.664ms   | 2.902MB   | 14.574MB  |
| 100000 x 16  | 1 600 000  | 262144  | 351.500ms  | 3 489.926ms   | 18.652MB  | 30.453MB  |
| 100000 x 16  | 1 600 000  | 524288  | 709.562ms  | 3 513.428ms   | 36.652MB  | 48.477MB  |
| 100000 x 32  | 3 200 000  | 1024    | 2.558ms    | 6 636.691ms   | 0.811MB   | 12.488MB  |
| 100000 x 32  | 3 200 000  | 32768   | 43.137ms   | 6 855.266ms   | 2.905MB   | 14.594MB  |
| 100000 x 32  | 3 200 000  | 262144  | 350.783ms  | 7 092.668ms   | 18.655MB  | 30.422MB  |
| 100000 x 32  | 3 200 000  | 524288  | 709.272ms  | 7 187.225ms   | 36.655MB  | 48.496MB  |
| 100000 x 64  | 6 400 000  | 1024    | 3.733ms    | 13 572.875ms  | 0.811MB   | 12.527MB  |
| 100000 x 64  | 6 400 000  | 32768   | 45.658ms   | 14 091.011ms  | 2.914MB   | 14.621MB  |
| 100000 x 64  | 6 400 000  | 262144  | 349.527ms  | 14 680.272ms  | 18.664MB  | 30.391MB  |
| 100000 x 64  | 6 400 000  | 524288  | 698.834ms  | 15 201.110ms  | 36.664MB  | 48.473MB  |
