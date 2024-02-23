<?php
namespace TYGHaykal\LaravelSeedGenerator\Helpers;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class SeederHelper
{
    public static function updateDatabaseSeeder(Filesystem $files, string $seedClassName): bool
    {
        $laravelBefore8 = version_compare(app()->version(), "8.0.0") < 0;
        $seederDir = $laravelBefore8 ? 'seeds' : 'seeders';

        $databaseSeederPath = database_path() . "/" . $seederDir . '/DatabaseSeeder.php';

        $content = $files->get($databaseSeederPath);
        $indentChar = StringHelper::generateIndentation("", 1);
        if (strpos($content, "\$this->call({$seedClassName}::class)") === false) {
            $content = preg_replace(
                "/(run\(\).+?)}/us",
                "$1{$indentChar}\$this->call({$seedClassName}::class);\n{$indentChar}}",
                $content
            );
        }
        return $files->put($databaseSeederPath, $content) !== false;
    }
}
