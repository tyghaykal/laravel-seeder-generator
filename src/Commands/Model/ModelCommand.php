<?php
namespace TYGHaykal\LaravelSeedGenerator\Commands\Model;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use TYGHaykal\LaravelSeedGenerator\Helpers\SeederHelper;
use TYGHaykal\LaravelSeedGenerator\Helpers\StringHelper;

class ModelCommand
{
    private $parentCommand, $files, $config;

    public function __construct(Command $parentCommand, Filesystem $files, Repository $config)
    {
        $this->parentCommand = $parentCommand;
        $this->files = $files;
        $this->config = $config;
    }

    public function handle()
    {
        $models = $this->parentCommand->getModels();
        $updateDatabaseSeeder = $this->parentCommand->getUpdateDatabaseSeeder();
        foreach ($models as $model) {
            $modelInstance = $this->parentCommand->getModelInstance($model);
            $seedCode = $this->getSeederCode($modelInstance);

            $seedClassName = $this->writeSeederFile($seedCode, $modelInstance, $this->parentCommand->getOutputLocation());
            if ($updateDatabaseSeeder) {
                SeederHelper::updateDatabaseSeeder($this->files, $seedClassName);
            }
        }

        if ($updateDatabaseSeeder) {
            $this->parentCommand->info("DatabaseSeeder file updated");
        }
    }

    private function getSeederCode(Model $modelInstance): string
    {
        $selectedIds = $this->parentCommand->getSelectedIds();
        $ignoreIds = $this->parentCommand->getIgnoredIds();

        $databaseConnection = $this->config->get("seed-generator.database_connection");
        $modelInstance = $modelInstance->on($databaseConnection);

        if (count($selectedIds) > 0) {
            $modelInstance = $modelInstance->whereIn("id", $selectedIds);
        } elseif (count($ignoreIds) > 0) {
            $modelInstance = $modelInstance->whereNotIn("id", $ignoreIds);
        }

        $whereRawQuery = $this->parentCommand->getWhereRawQuery();
        if ($whereRawQuery) {
            $modelInstance = $modelInstance->whereRaw($whereRawQuery);
        }

        $wheres = $this->parentCommand->getWheres();
        if ($wheres) {
            foreach ($wheres as $whereData) {
                $modelInstance = $modelInstance->where($whereData["column"], $whereData["type"], $whereData["value"]);
            }
        }

        $whereIns = $this->parentCommand->getWhereIns();
        if (count($whereIns) > 0) {
            foreach ($whereIns as $whereInData) {
                $modelInstance = $modelInstance->whereIn($whereInData["column"], $whereInData["value"]);
            }
        }

        $whereNotIns = $this->parentCommand->getWhereNotIns();
        if (count($whereNotIns) > 0) {
            foreach ($whereNotIns as $whereNotInData) {
                $modelInstance = $modelInstance->whereNotIn($whereNotInData["column"], $whereNotInData["value"]);
            }
        }

        $orderBy = $this->parentCommand->getOrderBy();
        if ($orderBy) {
            $modelInstance = $modelInstance->orderBy($orderBy['column'], $orderBy['direction']);
        }

        $limit = $this->parentCommand->getLimit();
        if ($limit) {
            $modelInstance = $modelInstance->limit($limit);
        }
        $modelDatas = $modelInstance->get();

        $codes = [];
        $relations = $this->parentCommand->getRelations();
        $relationsLimit = $this->parentCommand->getRelationLimits();
        $selectedFields = $this->parentCommand->getSelectedFields();
        $ignoredFields = $this->parentCommand->getIgnoredFields();
        foreach ($modelDatas as $key => $data) {
            $data->makeHidden($relations);
            $dataArray = $data->getAttributes() ?? [];
            if (count($selectedFields) > 0) {
                //remove all fields except the selected fields
                $dataArray = array_intersect_key($dataArray, array_flip($selectedFields));
            }
            if (count($ignoredFields) > 0) {
                //return all fields except the ignored fields
                $dataArray = array_diff_key($dataArray, array_flip($ignoredFields));
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

    private function writeSeederFile(string $code, Model $modelInstance, ?string $outputLocation = null): string
    {
        $isReplace = false;
        $prefix = $this->config->get("seed-generator.prefix");
        $suffix = $this->config->get("seed-generator.suffix");
        $exportNamespace = $this->config->get("seed-generator.export_namespace");
        $exportModelNamespace = $this->config->get("seed-generator.export_model_namespace");

        if ($outputLocation == null) {
            //set seed class name
            $seedClassName = class_basename($modelInstance);
            $seedClassName = Str::studly($prefix . $seedClassName . $suffix);

            //set seed namespace
            $seedNamespace = new \ReflectionClass($modelInstance);
            $seedNamespace = $seedNamespace->getNamespaceName();
            $seedNamespace = $exportNamespace . $exportModelNamespace . str_replace("App\\Models", "", $seedNamespace);
        } else {
            $outputLocation = $exportNamespace . $exportModelNamespace . "/" . $outputLocation;
            if (!$this->parentCommand->isOldLaravelVersion()) {
                $seedNamespace = str_replace("Database\\Seeders", "", $outputLocation);
                $seedNamespace = str_replace("Database/Seeders", "", $outputLocation);
            } else {
                $seedNamespace = str_replace("Database\\Seeds", "", $outputLocation);
                $seedNamespace = str_replace("Database/Seeds", "", $outputLocation);
            }

            $seedNamespace = str_replace("/", "\\", $seedNamespace);

            $seedClassName = class_basename($modelInstance);
            $seedClassName = Str::studly($prefix . $seedClassName . $suffix);
        }

        $command = $this->parentCommand->getRunCommand();

        if (!$this->parentCommand->isOldLaravelVersion()) {
            $stubContent = $this->files->get(__DIR__ . "/../../Stubs/SeedModelAfter8.stub");
            $fileContent = str_replace(
                ["{{ namespace }}", "{{ class }}", "{{ command }}", "{{ code }}"],
                [$seedNamespace, $seedClassName, $command, $code],
                $stubContent
            );
        } else {
            $stubContent = $this->files->get(__DIR__ . "/../../Stubs/SeedModelBefore8.stub");
            $fileContent = str_replace(
                ["{{ class }}", "{{ command }}", "{{ code }}"],
                [$seedClassName, $command, $code],
                $stubContent
            );
        }

        $dirSeed = $seedNamespace ? $seedNamespace : "";
        $dirSeed = SeederHelper::lowerCaseNamespace($dirSeed);
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

        return "\\" . $seedNamespace . "\\" . $seedClassName;
    }
}
