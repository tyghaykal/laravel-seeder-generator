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
        $indentation = str_repeat("    ", $indentationLevel);
        $prettyArrayStrings = [];

        foreach ($array as $key => $value) {
            if (is_string($value) && preg_match("/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z/", $value)) {
                $value = \Carbon\Carbon::parse($value)->format("Y-m-d H:i:s");
            }
            // Only convert to integer if it's a pure numeric value (not a string that starts with numbers)
            // This prevents table/column names like "000_users" or "0name" from being converted to integers
            if (is_numeric($value) && intval($value) == $value && !is_string($value)) {
                $value = intval($value);
            }
            $prettyValue = var_export($value, true);

            $prettyArrayStrings[] = "{$indentation}" . var_export($key, true) . " => {$prettyValue},";
        }

        $stringResult = "[\n" . implode("\n", $prettyArrayStrings) . "\n" . str_repeat("    ", $indentationLevel - 1) . "]";
        $stringResult = str_replace(["array (", ")"], ["[", "]"], $stringResult);
        $stringResult = rtrim($stringResult, ",]") . "]";
        return $stringResult;
    }

    public static function generateIndentation(string $string, int $indentationLevel = 1): string
    {
        $indentation = str_repeat("    ", $indentationLevel);
        $string = "{$indentation}{$string}";
        return $string;
    }
}
