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
            // Only convert to integer if it's a pure numeric value
            // But preserve leading zeros in identifiers (table/column names) by checking if the key suggests it's an identifier
            // Identifiers typically have underscores, start with letters, or contain special characters
            $isIdentifier = is_string($key) && (
                strpos($key, '_') !== false || 
                preg_match('/^[a-zA-Z]/', $key) || 
                preg_match('/^[0-9]+[a-zA-Z_]/', $key) ||
                preg_match('/^[0-9]+_[a-zA-Z]/', $key)
            );
            
            if (is_numeric($value) && intval($value) == $value && !$isIdentifier) {
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
