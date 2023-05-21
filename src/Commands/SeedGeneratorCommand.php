<?php
namespace TYGHaykal\LaravelSeedGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use TYGHaykal\LaravelSeedGenerator\Helpers\StringHelper;

class SeedGeneratorCommand extends Command
{
    protected $signature = "seed:generate {model?} {--no-additional} {--all-ids} {--all-fields} {--without-relations} {--ids= : The ids to be seeded} {--ignore-ids= : The ids to be ignored} {--fields= : The fields to be seeded} {--ignore-fields= : The fields to be ignored} {--relations= : The relations to be seeded}";
    protected $description = "Generate a seed file from a model";
    private $oldLaravelVersion = false;
    public function __construct__()
    {
        parent::__construct();
        $this->oldLaravelVersion = version_compare(app()->version(), "8.0.0") < 0;
    }

    public function handle(Filesystem $files)
    {
        try {
            $model = $this->checkModelInput("model");
            $modelInstance = app($model);

            if ($this->option("no-additional")) {
                $this->info("No option selected. All data will be seeded.");
                $this->info(
                    "You can use --all-ids, --all-fields, --without-relations, --ids, --ignore-ids, --fields, --ignore-fields, --relations options to customize the seed file."
                );
                $selectedIds = [];
                $ignoreIds = [];
                $selectedFields = [];
                $ignoreFields = [];
                $relations = [];
            } else {
                list($selectedIds, $ignoreIds) = $this->checkIdsInput();
                list($selectedFields, $ignoreFields) = $this->checkFieldsInput();
                $relations = $this->checkRelationInput();
            }

            $seederCommands = $this->getSeederCode(
                $modelInstance,
                $selectedIds,
                $ignoreIds,
                $selectedFields,
                $ignoreFields,
                $relations
            );

            $this->writeSeederFile($files, $seederCommands, $modelInstance);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }

    private function checkModelInput(): string
    {
        $model = $this->argument("model");
        if (!$model) {
            $model = $this->anticipate("Please provide a model name", []);
        }
        return $this->checkModel($model);
    }

    private function checkModel(string $model): string
    {
        if ($this->oldLaravelVersion) {
            $modelPath = "\\App\\{$model}";
            if (class_exists($modelPath)) {
                return "\\App\\$model";
            }
            throw new \Exception("Model file not found at {$modelPath}");
        } else {
            $modelPath = "\\App\\Models\\{$model}";
            if (class_exists($modelPath)) {
                return "\\App\\Models\\$model";
            }
            throw new \Exception("Model file not found at {$modelPath}");
        }
    }

    private function checkIdsInput(): array
    {
        if ($this->option('all-ids')) {
            return [[], []];
        }
        $selectedIds = $this->option("ids");
        $ignoredIds = $this->option("ignore-ids");
        if ($selectedIds == null && $ignoredIds == null) {
            $typeOfIds = $this->choice("Do you want to select or ignore ids?", [
                1 => "Select all",
                2 => "Select some ids",
                3 => "Ignore some ids",
            ]);
            switch ($typeOfIds) {
                case "Select some ids":
                    $selectedIds = $this->ask("Please provide the ids you want to select (seperate with comma)");
                    break;
                case "Ignore some ids":
                    $ignoredIds = $this->ask("Please provide the ids you want to ignore (seperate with comma)");
                    break;
            }
        }
        $selectedIds = $this->optionToArray($selectedIds);
        $ignoredIds = $this->optionToArray($ignoredIds);
        if (count($selectedIds) > 0 && count($ignoredIds) > 0) {
            throw new \Exception("You can't use --ignore-ids and --ids at the same time.");
        }
        return [$selectedIds, $ignoredIds];
    }

    private function checkFieldsInput(): array
    {
        if ($this->option('all-fields')) {
            return [[], []];
        }
        $selectedFields = $this->option("fields");
        $ignoredFields = $this->option("ignore-fields");
        if ($selectedFields == null && $ignoredFields == null) {
            $typeOfFields = $this->choice("Do you want to select or ignore fields?", [
                1 => "Select all",
                2 => "Select some fields",
                3 => "Ignore some fields",
            ]);
            switch ($typeOfFields) {
                case "Select some fields":
                    $selectedFields = $this->ask("Please provide the fields you want to select (seperate with comma)");
                    break;
                case "Ignore some fields":
                    $ignoredFields = $this->ask("Please provide the fields you want to ignore (seperate with comma)");
                    break;
            }
        }
        $selectedFields = $this->optionToArray($selectedFields);
        $ignoredFields = $this->optionToArray($ignoredFields);
        if (count($selectedFields) > 0 && count($ignoredFields) > 0) {
            throw new \Exception("You can't use --ignore-fields and --fields at the same time.");
        }
        return [$selectedFields, $ignoredFields];
    }

    private function checkRelationInput(): array
    {
        if (!$this->option("without-relations")) {
            $relations = $this->option("relations");
            if ($relations == null) {
                $typeOfRelation = $this->choice("Do you want to seed the has-many relation?", [1 => "No", 2 => "Yes"]);
                switch ($typeOfRelation) {
                    case "Yes":
                        $relations = $this->ask("Please provide the has-many relations you want to seed (seperate with comma)");
                        break;
                    default:
                        $relations = "";
                        break;
                }
            }
            $relations = $this->optionToArray($relations);
            return $relations;
        }
        return [];
    }

    private function optionToArray(?string $ids): array
    {
        if (!$ids) {
            return [];
        }
        $ids = explode(",", $ids);
        return $ids;
    }

    private function getSeederCode(
        Model $modelInstance,
        array $selectedIds,
        array $ignoreIds,
        array $selectedFields,
        array $ignoreFields,
        array $relations
    ): string {
        $modelInstance = $modelInstance->newQuery();
        if (count($selectedIds) > 0) {
            $modelDatas = $modelInstance->whereIn("id", $selectedIds)->get();
        } elseif (count($ignoreIds) > 0) {
            $modelDatas = $modelInstance->whereNotIn("id", $ignoreIds)->get();
        } else {
            $modelDatas = $modelInstance->get();
        }
        $codes = [];
        foreach ($modelDatas as $key => $data) {
            $dataArray = $data->toArray() ?? [];
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
                $relationData = $data->$relation;
                //get the has many relation only
                if ($data->$relation() instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
                    if ($relationData->count() > 0) {
                        $relationSubDatas = $relationData->toArray();
                        $relationCode = "";
                        foreach ($relationSubDatas as $subRelationKey => $relationSubData) {
                            $relationSubData = StringHelper::prettyPrintArray($relationSubData, 4);
                            if ($subRelationKey > 0) {
                                $relationSubData = StringHelper::generateIndentation($relationSubData, 4);
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

    private function writeSeederFile(Filesystem $files, string $code, Model $modelInstance): void
    {
        $isReplace = false;

        //set seed class name
        $seedClassName = class_basename($modelInstance);
        $seedClassName = Str::studly($seedClassName) . "Seeder";
        //set seed namespace
        $seedNamespace = new \ReflectionClass($modelInstance);
        $seedNamespace = $seedNamespace->getNamespaceName();
        $seedNamespace = str_replace("App\\Models", "", $seedNamespace);

        if (!$this->oldLaravelVersion) {
            $dirSeed = "seeders";
            $stubContent = $files->get(__DIR__ . "/../Stubs/SeedAfter8.stub");
            $fileContent = str_replace(
                ["{{ namespace }}", "{{ class }}", "{{ code }}"],
                [$seedNamespace, $seedClassName, $code],
                $stubContent
            );
        } else {
            $dirSeed = "seeds";
            $stubContent = $files->get(__DIR__ . "/../Stubs/SeedBefore8.stub");
            $fileContent = str_replace(["{{ class }}", "{{ code }}"], [$seedClassName, $code], $stubContent);
        }

        $dirSeed .= $seedNamespace ? $seedNamespace : "";

        //check if seed directory exists
        if (!$files->exists(database_path($dirSeed))) {
            $files->makeDirectory(database_path($dirSeed));
        }

        //get $modelInstance namespace
        $filePath = database_path("{$dirSeed}" . ("\\" . $seedClassName) . ".php");

        if ($files->exists($filePath)) {
            $isReplace = true;
            $files->delete($filePath);
        }

        $files->put($filePath, $fileContent);

        $this->info(($isReplace ? "Seed file replaced" : "Seed file created") . " : {$filePath}");
    }
}
