<?php

declare(strict_types=1);

use Illuminate\Support\Stringable;

if(! file_exists('foreignStrNewLne')) {
    function newLineWithTab(Stringable $str, int $countTab = 4): Stringable
    {
        $str = $str->newLine();

        for($i = 0; $i < $countTab; $i++) {
            $str = $str->append('    ');
        }

        return $str;
    }
}