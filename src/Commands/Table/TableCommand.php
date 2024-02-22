<?php
namespace TYGHaykal\LaravelSeedGenerator\Commands\Table;

use PDO;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Filesystem\Filesystem;
use TYGHaykal\LaravelSeedGenerator\Helpers\StringHelper;

class TableCommand extends Command
{
    private Command $parentCommand;
    private Filesystem $files;

    public function __construct(Command $parentCommand, Filesystem $files)
    {
        parent::__construct();
        $this->parentCommand = $parentCommand;
        $this->files = $files;
    }

    public function handle()
    {
        $tables = self::getTables($this->parentCommand->getSelectedTables());

        foreach ($tables as $table) {
            $this->createSeed($table);
        }
        //     return false;
    }

    public function getTables(?array $selectedTables = []): array
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

    public function getTableData(string $table)
    {
        $data = DB::table($table);

        if ($this->parentCommand->getWhereRawQuery()) {
            $data = $data->whereRaw($this->parentCommand->getWhereRawQuery());
        }

        if ($this->parentCommand->getWheres()) {
            foreach ($this->parentCommand->getWheres() as $where) {
                $data = $data->where($where["column"], $where["type"], $where["value"]);
            }
        }
        return $data->get();
    }

    public function createSeed(string $table): mixed
    {
        //get data
        $tableDatas = $this->getTableData($table);
        $code = "";
        foreach ($tableDatas as $key => $tableData) {
            if ($key != 0) {
                $code .= ",\n" . StringHelper::generateIndentation("", 3);
            }
            $code .= StringHelper::prettyPrintArray((array) $tableData, 4);
        }

        $code = "[\n" . StringHelper::generateIndentation($code, 3) . "\n" . StringHelper::generateIndentation("]", 2);

        return $this->writeSeederFile($code, $table);
    }

    private function writeSeederFile(string $code, string $tableName, ?string $outputLocation = null): void
    {
        $isReplace = false;

        if ($outputLocation == null) {
            //set seed class name
            $seedClassName = Str::studly($tableName) . "Seeder";

            $seedNamespace = "/Tables";
        } else {
            if (!$this->parentCommand->oldLaravelVersion) {
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

        // $this->parentCommandString = $this->parentCommand->getCommands();
        $this->parentCommandString = "php artisan ";

        $dirSeed = "seeders";
        $stubContent = $this->files->get(__DIR__ . "/../../Stubs/SeedTable.stub");
        $fileContent = str_replace(
            ["{{ class }}", "{{ command }}", "{{ code }}", "{{ table }}"],
            [$seedClassName, $this->parentCommandString, $code, $tableName],
            $stubContent
        );

        $dirSeed .= $seedNamespace ? $seedNamespace : "";
        $dirSeed = str_replace("\\", "/", $dirSeed);
        $dirSeedExploded = preg_split("/[\\\\\/]/", $dirSeed);
        $dirSeedCreation = "";
        foreach ($dirSeedExploded as $key => $dirSeedExplodedData) {
            $dirSeedCreation .= ($key > 0 ? "/" : "") . $dirSeedExplodedData;
            if (!$this->files->exists(database_path($dirSeedCreation))) {
                $this->files->makeDirectory(database_path($dirSeedCreation));
            }
        }
        //get $modelInstance namespace
        $filePath = database_path("{$dirSeed}" . ("/" . $seedClassName) . ".php");
        if ($this->files->exists($filePath)) {
            $isReplace = true;
            $this->files->delete($filePath);
        }
        $this->files->put($filePath, $fileContent);

        $this->parentCommand->info(($isReplace ? "Seed file replaced" : "Seed file created") . " : {$filePath}");
    }
}
