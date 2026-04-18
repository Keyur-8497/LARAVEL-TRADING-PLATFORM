<?php

use Illuminate\Support\Str;

if (! function_exists('RendomString')) {
    function RendomString($size = 10, $type = 'mix')
    {
        /* Type : 'number','string','mix' */
        $size = $size == null ? 10 : $size;
        $code = '';

        if ($type == 'number') {
            $akeys = range('0', '9');

            for ($i = 0; $i < $size; $i++) {
                $code .= $akeys[array_rand($akeys)];
            }
        } elseif ($type == 'string') {
            $akeys = range('A', 'Z');
            $bkeys = range('a', 'z');
            $ckeys = array_merge($akeys, $bkeys);

            for ($i = 0; $i < $size; $i++) {
                $code .= $ckeys[array_rand($ckeys)];
            }
        } else {
            $code = Str::random($size);
        }

        return str_shuffle($code);
    }
}
