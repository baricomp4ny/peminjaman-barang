<?php

if (! function_exists('rupiah')) {
    /** Format uang Indonesia, contoh: rupiah(15000) → "Rp15.000". */
    function rupiah(int|float $nilai): string
    {
        return 'Rp'.number_format($nilai, 0, ',', '.');
    }
}
