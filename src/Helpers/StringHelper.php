<?php
namespace TYGHaykal\LaravelSeedGenerator\Helpers;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class StringHelper
{
    public static function prettyPrintArray($array, $indentationLevel = 1): string
    {
        // 4 spaces for each indentation level
        $indentation = str_repeat('    ', $indentationLevel); 
        $prettyArrayStrings = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $prettyValue = prettyPrintArray($value, $indentationLevel + 1);
            } else {
                $prettyValue = var_export($value, true);
            }

            $prettyArrayStrings[] = "{$indentation}" . var_export($key, true) . " => {$prettyValue},";
        }

        return "[\n" . implode("\n", $prettyArrayStrings) . "\n" . str_repeat('    ', $indentationLevel - 1) . "]";
    }

    public static function generateIndentation(string $string, int $indentationLevel = 1):string
    {
        $indentation = str_repeat('    ', $indentationLevel); 
        $string = "{$indentation}{$string}";
        return $string;
    }
}