<?php
namespace TYGHaykal\LaravelSeedGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use TYGHaykal\LaravelSeedGenerator\Commands\Table\TableCommand;
use TYGHaykal\LaravelSeedGenerator\Helpers\StringHelper;
use TYGHaykal\LaravelSeedGenerator\Helpers\TableHelper;
use TYGHaykal\LaravelSeedGenerator\Traits\CommandTrait;

class SeedGeneratorCommand extends Command
{
    use CommandTrait;
    protected $signature = "seed:generate {model?} 
                                {--show-prompt} 
                                {--all-ids} 
                                {--all-fields} 
                                {--without-relations} 
                                {--where-raw-query= : The raw query conditions}
                                {--where=* : The where clause conditions}
                                {--where-in=* : The where in clause conditions}
                                {--where-not-in=* : The where in clause conditions}
                                {--order-by= : Order data to be seeded} 
                                {--limit= : Limit data to be seeded} 
                                {--ids= : The ids to be seeded} 
                                {--ignore-ids= : The ids to be ignored} 
                                {--fields= : The fields to be seeded} 
                                {--ignore-fields= : The fields to be ignored} 
                                {--relations= : The relations to be seeded}
                                {--relations-limit= : Limit relation data to be seeded}
                                {--output= : Output file will be located on this path} 

                                {--model-mode : Set the resource mode to model} 


                                {--mode= : Set the resource mode (table or model)} 
                                {--table-mode : Set the resource mode to table} 
                                {--all-tables : Generate seed for all tables}
                                {--tables= : Generate seed for selected tables}";

    protected $description = "Generate a seed file from a model";
    private $oldLaravelVersion = false,
        $commands = [],
        $showPrompt = false;
    public function __construct()
    {
        parent::__construct();
        $this->commands["main"] = "artisan seed:generate";
        $this->oldLaravelVersion = version_compare(app()->version(), "8.0.0") < 0;
    }

    public function handle(Filesystem $files)
    {
        try {
            $this->showPrompt = $this->option("show-prompt");

            $mode = $this->getMode();
            switch ($mode) {
                case 'table':
                    $this->checkSelectedTableInput()
                        ->checkWhereRawQueryInput()
                        ->checkWhereInput()
                        ->checkWhereInInput()
                        ->checkWhereNotInInput()
                        ->checkOrderByInput()
                        ->checkLimitInput()
                        ->checkIdsInput()
                        ->checkFieldsInput()
                        ->checkOutputLocationInput();

                    return (new TableCommand($this, $files))->handle();

                default:
                    throw new \Exception("Mode $mode not supported, only 'table' and 'model' are supported");
            }

            // if ($this->option('table-mode')) {
            //     return (new TableCommand($this, $files))->handle();
            // }

            // $model = $this->checkModelInput("model");
            // $modelInstance = app($model);

            // $limit = $this->checkLimit();
            // list($selectedIds, $ignoreIds) = $this->checkIdsInput();
            // list($selectedFields, $ignoreFields) = $this->checkFieldsInput();
            // $relations = $this->checkRelationInput();
            // $relationsLimit = $this->checkRelationLimit();
            // $outputLocation = $this->checkOutput();

            // $seederCommands = $this->getSeederCode(
            //     $modelInstance,
            //     $selectedIds,
            //     $ignoreIds,
            //     $selectedFields,
            //     $ignoreFields,
            //     $relations,
            //     $where,
            //     $whereIn,
            //     $limit,
            //     $relationsLimit
            // );

            // $this->writeSeederFile($files, $seederCommands, $modelInstance, $outputLocation);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }

    // private function checkModelInput(): string
    // {
    //     $model = $this->argument("model");
    //     if (!$model && !$this->option('all-tables')) {
    //         $model = $this->anticipate("Please provide a model name", []);
    //     }
    //     $this->commands["main"] .= " " . $model;
    //     return $this->checkModel($model);
    // }

    // private function checkModel(string $model): string
    // {
    //     $modelPath = "\\App\\Models\\{$model}";
    //     if (class_exists($modelPath)) {
    //         return "\\App\\Models\\$model";
    //     } else {
    //         $modelPath = "\\App\\{$model}";
    //         if (class_exists($modelPath)) {
    //             return "\\App\\$model";
    //         }
    //     }
    //     throw new \Exception("Model file not found at under \App\Models or \App");
    // }

    // private function checkRelationLimit()
    // {
    //     $limit = $this->option("relations-limit");
    //     if ($limit == null && $this->showPrompt) {
    //         $typeLimitRelation = $this->choice("Do you want to use limit in relation?", [
    //             1 => "No",
    //             2 => "Yes",
    //         ]);
    //         switch ($typeLimitRelation) {
    //             case "Yes":
    //                 $limit = $this->ask("Please provide the limit of relation data to be seeded");
    //                 break;
    //         }
    //     }
    //     if ($limit != null) {
    //         $this->commands["relations-limit"] = "--relations-limit={$limit}";
    //     }

    //     return $limit;
    // }

    // private function checkRelationInput(): array
    // {
    //     if (!$this->option("without-relations")) {
    //         $relations = $this->option("relations");
    //         if ($relations == null && $this->showPrompt) {
    //             $typeOfRelation = $this->choice("Do you want to seed the has-many relation?", [1 => "No", 2 => "Yes"]);
    //             switch ($typeOfRelation) {
    //                 case "Yes":
    //                     $relations = $this->ask("Please provide the has-many relations you want to seed (seperate with comma)");
    //                     break;
    //                 default:
    //                     $relations = "";
    //                     break;
    //             }
    //         }
    //         if ($relations != null) {
    //             $this->commands["relation"] = "--relations={$relations}";
    //         }
    //         $relations = $this->optionToArray($relations);
    //         return $relations;
    //     }
    //     return [];
    // }

    private function getSeederCode(
        Model $modelInstance,
        array $selectedIds,
        array $ignoreIds,
        array $selectedFields,
        array $ignoreFields,
        array $relations,
        array $where,
        array $whereIn,
        ?int $limit,
        ?int $relationsLimit
    ): string {
        $modelInstance = $modelInstance->newQuery();
        if (count($selectedIds) > 0) {
            $modelInstance = $modelInstance->whereIn("id", $selectedIds);
        } elseif (count($ignoreIds) > 0) {
            $modelInstance = $modelInstance->whereNotIn("id", $ignoreIds);
        }

        if (count($where) > 0) {
            foreach ($where as $whereData) {
                $modelInstance = $modelInstance->where($whereData["column"], $whereData["value"]);
            }
        }

        if (count($whereIn) > 0) {
            foreach ($whereIn as $whereInData) {
                $modelInstance = $modelInstance->whereIn($whereInData["column"], $whereInData["value"]);
            }
        }

        if ($limit != null) {
            $modelInstance = $modelInstance->limit($limit);
        }
        $modelDatas = $modelInstance->get();

        $codes = [];
        foreach ($modelDatas as $key => $data) {
            $data->makeHidden($relations);
            $dataArray = $data->getAttributes() ?? [];
            if (count($selectedFields) > 0) {
                //remove all fields except the selected fields
                $dataArray = array_intersect_key($dataArray, array_flip($selectedFields));
            }
            if (count($ignoreFields) > 0) {
                //return all fields except the ignored fields
                $dataArray = array_diff_key($dataArray, array_flip($ignoreFields));
            }
            $dataArray = StringHelper::prettyPrintArray($dataArray, 3);

            $code = "\$newData$key = \\" . get_class($modelInstance->getModel()) . "::create(" . $dataArray . ");";

            if ($key != 0) {
                $code = StringHelper::generateIndentation($code, 2);
            }
            foreach ($relations as $relation) {
                $relationData = $data->$relation->take($relationsLimit);
                //get the has many relation only
                if ($data->$relation() instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
                    if ($relationData->count() > 0) {
                        $relationSubDatas = $relationData
                            ->map(function ($relationData) {
                                return $relationData->getAttributes();
                            })
                            ->toArray();
                        $relationCode = "";
                        foreach ($relationSubDatas as $subRelationKey => $relationSubData) {
                            $relationSubData = StringHelper::prettyPrintArray($relationSubData, 4);
                            if ($subRelationKey > 0) {
                                $relationSubData = StringHelper::generateIndentation($relationSubData, 3);
                            } else {
                                $relationSubData = StringHelper::generateIndentation($relationSubData, 3);
                            }
                            $relationCode .= ($subRelationKey > -1 ? "\n" : "") . $relationSubData . ",";
                        }
                        // Remove trailing comma
                        $relationCode = rtrim($relationCode, ",]") . "]";
                        $relationCode = "\$newData$key->$relation()->createMany([" . $relationCode;
                        $relationCode = "\n" . StringHelper::generateIndentation($relationCode, 2);
                        $relationCode .= "\n" . StringHelper::generateIndentation("]);", 2);
                        $code .= $relationCode;

                        // $code = StringHelper::generateIndentation($code, 1);
                    }
                } else {
                    throw new \Exception("The relation {$relation} is not a has-many relation");
                }
            }
            $codes[] = $code;
        }
        $code = implode("\n", $codes);
        //remove tab from $code
        $code = str_replace("\t", "", $code);
        $code = str_replace("\r", "", $code);
        return $code;
    }

    private function getCommands(): string
    {
        // if ($this->showPrompt) {
        //     $this->commands["show_option"] = "--show-prompt";
        // }
        return implode(" ", $this->commands);
    }

    private function writeSeederFile(Filesystem $files, string $code, Model $modelInstance, ?string $outputLocation = null): void
    {
        $isReplace = false;

        if ($outputLocation == null) {
            //set seed class name
            $seedClassName = class_basename($modelInstance);
            $seedClassName = Str::studly($seedClassName) . "Seeder";

            //set seed namespace
            $seedNamespace = new \ReflectionClass($modelInstance);
            $seedNamespace = $seedNamespace->getNamespaceName();
            $seedNamespace = str_replace("App\\Models", "", $seedNamespace);
        } else {
            if (!$this->oldLaravelVersion) {
                $seedNamespace = str_replace("Database\\Seeders", "", $outputLocation);
                $seedNamespace = str_replace("Database/Seeders", "", $outputLocation);
            } else {
                $seedNamespace = str_replace("Database\\Seeds", "", $outputLocation);
                $seedNamespace = str_replace("Database/Seeds", "", $outputLocation);
            }

            $seedNamespace = str_replace("/", "\\", $seedNamespace);
            $seedNamespace = explode('\\', $seedNamespace);

            $seedClassName = Str::studly($seedNamespace[count($seedNamespace) - 1]) . "Seeder";
            unset($seedNamespace[count($seedNamespace) - 1]);

            //str studly for every $seedNamespace
            foreach ($seedNamespace as $key => $seedNamespaceData) {
                $seedNamespace[$key] = Str::studly($seedNamespaceData);
            }
            $seedNamespace = '\\' . implode('\\', $seedNamespace);
        }

        $command = $this->getCommands();

        if (!$this->oldLaravelVersion) {
            $dirSeed = "seeders";
            $stubContent = $files->get(__DIR__ . "/../Stubs/SeedAfter8.stub");
            $fileContent = str_replace(
                ["{{ namespace }}", "{{ class }}", "{{ command }}", "{{ code }}"],
                [$seedNamespace, $seedClassName, $command, $code],
                $stubContent
            );
        } else {
            $dirSeed = "seeds";
            $stubContent = $files->get(__DIR__ . "/../Stubs/SeedBefore8.stub");
            $fileContent = str_replace(
                ["{{ class }}", "{{ command }}", "{{ code }}"],
                [$seedClassName, $command, $code],
                $stubContent
            );
        }

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

        $this->info(($isReplace ? "Seed file replaced" : "Seed file created") . " : {$filePath}");
    }
}
