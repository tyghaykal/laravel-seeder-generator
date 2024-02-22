<?php
namespace TYGHaykal\LaravelSeedGenerator\Helpers;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PDO;

class TableHelper
{
    public static function getTables(): array
    {
        $databaseType = DB::connection()
            ->getPDO()
            ->getAttribute(PDO::ATTR_DRIVER_NAME);
        $ignoreTables = ['jobs', 'failed_jobs', 'migrations', 'cache'];

        switch ($databaseType) {
            case 'mysql':
                $query = 'SHOW TABLES';
                $tables = array_map('current', DB::select($query));
                break;

            case 'pgsql':
                $query = "SELECT table_name FROM information_schema.tables WHERE table_schema='public'";
                $tables = array_column(DB::select($query), 'table_name');
                break;

            case 'sqlite':
                $query = "SELECT name FROM sqlite_master WHERE type='table'";
                $tables = array_column(DB::select($query), 'name');
                break;

            case 'sqlsrv':
                $query =
                    "SELECT table_name FROM information_schema.tables WHERE table_type = 'BASE TABLE' AND table_catalog=DATABASE()";
                $tables = array_column(DB::select($query), 'table_name');
                break;

            default:
                throw new \Exception("Database type not supported: {$databaseType}");
                break;
        }

        // Filter out ignored tables
        $tables = array_filter($tables, function ($tableName) use ($ignoreTables) {
            return !in_array($tableName, $ignoreTables);
        });

        return array_values($tables);
        return $tables;
    }

    public static function createSeed(Command $command, string $table, Filesystem $files)
    {
        //get data
        $tableDatas = DB::table($table)->get();
        $code = "";
        foreach ($tableDatas as $key => $tableData) {
            if ($key != 0) {
                $code .= ",\n" . StringHelper::generateIndentation("", 3);
            }
            $code .= StringHelper::prettyPrintArray((array) $tableData, 4);
        }

        $code = "[\n" . StringHelper::generateIndentation($code, 3) . "\n" . StringHelper::generateIndentation("]", 2);

        // create seed file
        return self::writeSeederFile($command, $files, $code, $table);
    }

    private static function writeSeederFile(
        Command $command,
        Filesystem $files,
        string $code,
        string $tableName,
        ?string $outputLocation = null
    ): void {
        $isReplace = false;

        if ($outputLocation == null) {
            //set seed class name
            $seedClassName = Str::studly($tableName) . "Seeder";

            $seedNamespace = "/Tables";
        } else {
            if (!$command->oldLaravelVersion) {
                $seedNamespace = str_replace("Database\\Seeders\\Tables\\", "", $outputLocation);
                $seedNamespace = str_replace("Database/Seeders/Tables", "", $outputLocation);
            } else {
                $seedNamespace = str_replace("Database\\Seeds\\Tables", "", $outputLocation);
                $seedNamespace = str_replace("Database/Seeds/Tables", "", $outputLocation);
            }

            $seedNamespace = str_replace("/", "\\", $seedNamespace);
            $seedNamespace = explode('\\', $seedNamespace);

            $seedClassName = Str::studly($tableName) . "Seeder";

            //str studly for every $seedNamespace
            foreach ($seedNamespace as $key => $seedNamespaceData) {
                $seedNamespace[$key] = Str::studly($seedNamespaceData);
            }
            $seedNamespace = '\\' . implode('\\', $seedNamespace);
        }

        // $commandString = $command->getCommands();
        $commandString = "php artisan ";

        $dirSeed = "seeders";
        $stubContent = $files->get(__DIR__ . "/../Stubs/SeedTable.stub");
        $fileContent = str_replace(
            ["{{ class }}", "{{ command }}", "{{ code }}", "{{ table }}"],
            [$seedClassName, $commandString, $code, $tableName],
            $stubContent
        );

        $dirSeed .= $seedNamespace ? $seedNamespace : "";
        $dirSeed = str_replace("\\", "/", $dirSeed);
        $dirSeedExploded = preg_split("/[\\\\\/]/", $dirSeed);
        $dirSeedCreation = "";
        foreach ($dirSeedExploded as $key => $dirSeedExplodedData) {
            $dirSeedCreation .= ($key > 0 ? "/" : "") . $dirSeedExplodedData;
            if (!$files->exists(database_path($dirSeedCreation))) {
                $files->makeDirectory(database_path($dirSeedCreation));
            }
        }
        //get $modelInstance namespace
        $filePath = database_path("{$dirSeed}" . ("/" . $seedClassName) . ".php");
        if ($files->exists($filePath)) {
            $isReplace = true;
            $files->delete($filePath);
        }
        $files->put($filePath, $fileContent);

        $command->info(($isReplace ? "Seed file replaced" : "Seed file created") . " : {$filePath}");
    }
}
