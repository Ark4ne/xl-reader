<?php

namespace Ark4ne\XlReader\Reader;

use Generator;

interface IReader
{
    public function load();

    public function read(int $start_row = 0, null|int $end_row = null): Generator;
}
