<?php
namespace TYGHaykal\LaravelSeedGenerator\Commands\Table;

use Illuminate\Contracts\Config\Repository;
use PDO;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use TYGHaykal\LaravelSeedGenerator\Helpers\SeederHelper;
use TYGHaykal\LaravelSeedGenerator\Helpers\StringHelper;

class TableCommand
{
    private $parentCommand, $files, $config;

    public function __construct($parentCommand, Filesystem $files, Repository $config)
    {
        $this->parentCommand = $parentCommand;
        $this->files = $files;
        $this->config = $config;
    }

    public function handle(): void
    {
        $tables = self::getTables($this->parentCommand->getSelectedTables());
        $updateDatabaseSeeder = $this->parentCommand->getUpdateDatabaseSeeder();

        foreach ($tables as $table) {
            $seedClassName = $this->createSeed($table);

            if ($updateDatabaseSeeder) {
                SeederHelper::updateDatabaseSeeder($this->files, $seedClassName);
            }
        }

        if ($updateDatabaseSeeder) {
            $this->parentCommand->info("DatabaseSeeder file updated");
        }
    }

    public function getTables(?array $selectedTables = []): array
    {
        $databaseConnection = $this->config->get("seed-generator.database_connection");

        $databaseType = DB::connection($databaseConnection)
            ->getPDO()
            ->getAttribute(PDO::ATTR_DRIVER_NAME);
        $ignoreTables = ['jobs', 'failed_jobs', 'migrations', 'cache', "sqlite_sequence", "sessions"];

        switch ($databaseType) {
            case 'mysql':
                $query = 'SHOW TABLES';
                $tables = array_map('current', DB::connection($databaseConnection)->select($query));
                break;

            case 'pgsql':
                $query = "SELECT table_name FROM information_schema.tables WHERE table_schema='public'";
                $tables = array_column(DB::connection($databaseConnection)->select($query), 'table_name');
                break;

            case 'sqlite':
                $query = "SELECT name FROM sqlite_master WHERE type='table'";
                $tables = array_column(DB::connection($databaseConnection)->select($query), 'name');
                break;

            case 'sqlsrv':
                $query =
                    "SELECT table_name FROM information_schema.tables WHERE table_type = 'BASE TABLE' AND table_catalog=DATABASE()";
                $tables = array_column(DB::connection($databaseConnection)->select($query), 'table_name');
                break;

            default:
                throw new \Exception("Database type not supported: {$databaseType}");
                break;
        }

        $tables = array_filter($tables, function ($tableName) use ($ignoreTables) {
            return !in_array($tableName, $ignoreTables);
        });

        if (count($selectedTables)) {
            $tables = array_filter($tables, function ($tableName) use ($selectedTables) {
                return in_array($tableName, $selectedTables);
            });

            if (count($selectedTables) != count($tables)) {
                $notFoundTables = array_diff($selectedTables, $tables);
                throw new \Exception("Table(s) not found: " . implode(", ", $notFoundTables));
            }
        }

        return array_values($tables);
    }

    public function getTableData(string $table): Collection
    {
        $databaseConnection = $this->config->get("seed-generator.database_connection");
        $data = DB::connection($databaseConnection)->table($table);

        $whereRawQuery = $this->parentCommand->getWhereRawQuery();
        if ($whereRawQuery) {
            $data = $data->whereRaw($whereRawQuery);
        }

        $wheres = $this->parentCommand->getWheres();
        if ($wheres) {
            foreach ($wheres as $where) {
                $data = $data->where($where["column"], $where["type"], $where["value"]);
            }
        }

        $whereIns = $this->parentCommand->getWhereIns();
        if ($whereIns) {
            foreach ($whereIns as $whereIn) {
                $data = $data->whereIn($whereIn["column"], $whereIn["value"]);
            }
        }

        $whereNotIns = $this->parentCommand->getWhereNotIns();
        if ($whereNotIns) {
            foreach ($whereNotIns as $whereNotIn) {
                $data = $data->whereNotIn($whereNotIn["column"], $whereNotIn["value"]);
            }
        }

        $selectedIds = $this->parentCommand->getSelectedIds();
        $ignoredIds = $this->parentCommand->getIgnoredIds();
        if ($selectedIds) {
            $data = $data->whereIn("id", $selectedIds);
        } elseif ($ignoredIds) {
            $data = $data->whereNotIn("id", $ignoredIds);
        }

        $selectedFields = $this->parentCommand->getSelectedFields();
        if ($selectedFields) {
            $data = $data->select($selectedFields);
        }

        $ignoredFields = $this->parentCommand->getIgnoredFields();
        if (count($selectedFields) == 0 && count($ignoredFields) > 0) {
            $allColumns = DB::connection($this->parentCommand->getDatabaseConnection())
                ->getSchemaBuilder()
                ->getColumnListing($table);
            $data = $data->select(array_diff($allColumns, $ignoredFields));
        }

        $orderBy = $this->parentCommand->getOrderBy();
        if ($orderBy) {
            $data = $data->orderBy($orderBy['column'], $orderBy['direction']);
        }

        $limit = $this->parentCommand->getLimit();
        if ($limit) {
            $data = $data->limit($limit);
        }

        $tableDatas = $data->get();

        return $tableDatas;
    }

    public function createSeed(string $table): string
    {
        $tableDatas = $this->getTableData($table);
        $code = "";
        foreach ($tableDatas as $key => $tableData) {
            if ($key != 0) {
                $code .= ",\n" . StringHelper::generateIndentation("", 3);
            }
            $code .= StringHelper::prettyPrintArray((array) $tableData, 4);
        }

        $code = "[\n" . StringHelper::generateIndentation($code, 3) . "\n" . StringHelper::generateIndentation("]", 2);

        $outputLocation = $this->parentCommand->getOutputLocation();

        return $this->writeSeederFile($code, $table, $outputLocation);
    }

    private function writeSeederFile(string $code, string $tableName, ?string $outputLocation = null): string
    {
        $isReplace = false;
        $prefix = $this->config->get("seed-generator.prefix");
        $suffix = $this->config->get("seed-generator.suffix");
        $exportNamespace = $this->config->get("seed-generator.export_namespace");
        $exportTableNamespace = $this->config->get("seed-generator.export_table_namespace");

        if ($outputLocation == null) {
            $seedClassName = Str::studly($prefix . $tableName . $suffix);
            $seedNamespace = $exportNamespace . $exportTableNamespace;
        } else {
            $outputLocation = $exportNamespace . $exportTableNamespace . "/" . $outputLocation;
            if (!$this->parentCommand->isOldLaravelVersion()) {
                $seedNamespace = str_replace("Database\\Seeders\\Tables\\", "", $outputLocation);
                $seedNamespace = str_replace("Database/Seeders/Tables", "", $outputLocation);
            } else {
                $seedNamespace = str_replace("Database\\Seeds\\Tables", "", $outputLocation);
                $seedNamespace = str_replace("Database/Seeds/Tables", "", $outputLocation);
            }
            $seedNamespace = str_replace("/", "\\", $seedNamespace);

            $seedClassName = Str::studly($prefix . $tableName . $suffix);
        }

        if ($this->parentCommand->isOldLaravelVersion()) {
            $stubContent = $this->files->get(__DIR__ . "/../../Stubs/SeedTableBefore8.stub");
            $fileContent = str_replace(
                ["{{ class }}", "{{ command }}", "{{ code }}", "{{ table }}"],
                [$seedClassName, $this->parentCommand->getRunCommand(), $code, $tableName],
                $stubContent
            );
        } else {
            $stubContent = $this->files->get(__DIR__ . "/../../Stubs/SeedTableAfter8.stub");
            $fileContent = str_replace(
                ["{{ namespace }}", "{{ class }}", "{{ command }}", "{{ code }}", "{{ table }}"],
                [$seedNamespace, $seedClassName, $this->parentCommand->getRunCommand(), $code, $tableName],
                $stubContent
            );
        }

        $dirSeed = $seedNamespace ? $seedNamespace : "";
        $dirSeed = str_replace("\\", "/", $dirSeed);
        $dirSeedExploded = preg_split("/[\\\\\/]/", $dirSeed);
        $dirSeedCreation = "";

        foreach ($dirSeedExploded as $key => $dirSeedExplodedData) {
            $dirSeedCreation .= ($key > 0 ? "/" : "") . $dirSeedExplodedData;
            if (!$this->files->exists(base_path($dirSeedCreation))) {
                $this->files->makeDirectory(base_path($dirSeedCreation));
            }
        }
        //get $modelInstance namespace
        $filePath = base_path("{$dirSeed}" . ("/" . $seedClassName) . ".php");
        if ($this->files->exists($filePath)) {
            $isReplace = true;
            $this->files->delete($filePath);
        }
        $this->files->put($filePath, $fileContent);

        $this->parentCommand->info(($isReplace ? "Seed file replaced" : "Seed file created") . " : {$filePath}");

        return $seedNamespace . $seedClassName;
    }
}
