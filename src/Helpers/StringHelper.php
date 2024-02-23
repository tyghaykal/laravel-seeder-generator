<?php
namespace TYGHaykal\LaravelSeedGenerator\Helpers;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class StringHelper
{
    /**
     * Formats an array into a pretty-printed string representation.
     *
     * @param array $array The array to be pretty-printed.
     * @param int $indentationLevel The level of indentation for the output string. Default is 1.
     * @return string The pretty-printed string representation of the array.
     */
    public static function prettyPrintArray($array, $indentationLevel = 1): string
    {
        // 4 spaces for each indentation level
        $indentation = str_repeat("    ", $indentationLevel);
        $prettyArrayStrings = [];

        foreach ($array as $key => $value) {
            if (is_string($value) && preg_match("/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z/", $value)) {
                $value = \Carbon\Carbon::parse($value)->format("Y-m-d H:i:s");
            }
            if (is_numeric($value) && intval($value) == $value) {
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

    /**
     * Generates an indented string.
     *
     * @param string $string The string to be indented.
     * @param int $indentationLevel The level of indentation. Default is 1.
     * @return string The indented string.
     */
    public static function generateIndentation(string $string, int $indentationLevel = 1): string
    {
        $indentation = str_repeat("    ", $indentationLevel);
        $string = "{$indentation}{$string}";
        return $string;
    }
}
