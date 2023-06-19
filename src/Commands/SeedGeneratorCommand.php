<?php
namespace TYGHaykal\LaravelSeedGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use TYGHaykal\LaravelSeedGenerator\Helpers\StringHelper;

class SeedGeneratorCommand extends Command
{
    protected $signature = "seed:generate {model?} 
                                {--show-prompt} 
                                {--all-ids} 
                                {--all-fields} 
                                {--without-relations} 
                                {--where=* : The where clause conditions}
                                {--where-in=* : The where in clause conditions}
                                {--limit= : Limit data to be seeded} 
                                {--ids= : The ids to be seeded} 
                                {--ignore-ids= : The ids to be ignored} 
                                {--fields= : The fields to be seeded} 
                                {--ignore-fields= : The fields to be ignored} 
                                {--relations= : The relations to be seeded}
                                {--relations-limit= : Limit relation data to be seeded}
                                {--output= : Output file will be located on this path} ";

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

            $model = $this->checkModelInput("model");
            $modelInstance = app($model);

            $where = $this->checkWhereInput();
            $whereIn = $this->checkWhereInInput();
            $limit = $this->checkLimit();
            list($selectedIds, $ignoreIds) = $this->checkIdsInput();
            list($selectedFields, $ignoreFields) = $this->checkFieldsInput();
            $relations = $this->checkRelationInput();
            $relationsLimit = $this->checkRelationLimit();
            $outputLocation = $this->checkOutput();

            $seederCommands = $this->getSeederCode(
                $modelInstance,
                $selectedIds,
                $ignoreIds,
                $selectedFields,
                $ignoreFields,
                $relations,
                $where,
                $whereIn,
                $limit,
                $relationsLimit
            );

            $this->writeSeederFile($files, $seederCommands, $modelInstance, $outputLocation);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }

    private function checkOutput()
    {
        $outputLocation = $this->option("output");
        if ($outputLocation == null && $this->showPrompt) {
            $typeOutput = $this->choice("Do you want to change the output location?", [
                1 => "No",
                2 => "Yes",
            ]);
            switch ($typeOutput) {
                case "Yes":
                    $outputLocation = $this->ask("Please provide the output location");
                    break;
            }
        }
        if ($outputLocation != null) {
            $this->commands["output"] = "--output={$outputLocation}";
        }
        return $outputLocation;
    }

    private function checkModelInput(): string
    {
        $model = $this->argument("model");
        if (!$model) {
            $model = $this->anticipate("Please provide a model name", []);
        }
        $this->commands["main"] .= " " . $model;
        return $this->checkModel($model);
    }

    private function checkModel(string $model): string
    {
        $modelPath = "\\App\\Models\\{$model}";
        if (class_exists($modelPath)) {
            return "\\App\\Models\\$model";
        } else {
            $modelPath = "\\App\\{$model}";
            if (class_exists($modelPath)) {
                return "\\App\\$model";
            }
        }
        throw new \Exception("Model file not found at under \App\Models or \App");
    }

    private function checkLimit()
    {
        $limit = $this->option("limit");
        if ($limit == null && $this->showPrompt) {
            $typeLimit = $this->choice("Do you want to use limit in seeded data?", [
                1 => "No",
                2 => "Yes",
            ]);
            switch ($typeLimit) {
                case "Yes":
                    $limit = $this->ask("Please provide the limit of data to be seeded");
                    break;
            }
        }
        if ($limit != null) {
            $this->commands["limit"] = "--limit={$limit}";
        }
        return $limit;
    }

    private function checkRelationLimit()
    {
        $limit = $this->option("relations-limit");
        if ($limit == null && $this->showPrompt) {
            $typeLimitRelation = $this->choice("Do you want to use limit in relation?", [
                1 => "No",
                2 => "Yes",
            ]);
            switch ($typeLimitRelation) {
                case "Yes":
                    $limit = $this->ask("Please provide the limit of relation data to be seeded");
                    break;
            }
        }
        if ($limit != null) {
            $this->commands["relations-limit"] = "--relations-limit={$limit}";
        }

        return $limit;
    }

    private function checkWhereInput()
    {
        $wheres = $this->option("where");
        $this->commands["where"] = "";
        if (count($wheres) == 0 && $this->showPrompt) {
            $wheres = [];
            while (true) {
                $isMore = count($wheres) > 0;
                $typeWhere = $this->choice("Do you want to " . ($isMore ? "add more" : "use") . " where clause conditions?", [
                    1 => "No",
                    2 => "Yes",
                ]);
                switch ($typeWhere) {
                    case "Yes":
                        $wheres[] = $this->ask(
                            "Please provide the where clause conditions (seperate with comma for column and value)"
                        );
                        break;
                    default:
                        break 2;
                }
            }
        }
        if ($wheres != null) {
            foreach ($wheres as $key => $where) {
                $this->commands["where"] .= ($key > 0 ? " " : "") . "--where={$where}";
            }
        }
        $wheresFinal = [];
        foreach ($wheres as $key => $where) {
            $result = $this->optionToArray($where);
            if (count($result) != 2) {
                throw new \Exception("You must provide 2 values for where clause");
            }
            $wheresFinal[$key]["column"] = $result[0];
            $wheresFinal[$key]["value"] = $result[1];
        }
        if (count($wheresFinal) == 0) {
            unset($this->commands["where"]);
        }

        return $wheresFinal;
    }

    private function checkWhereInInput()
    {
        $whereIns = $this->option("where-in");
        $this->commands["where-in"] = "";
        if (count($whereIns) == 0 && $this->showPrompt) {
            $whereIns = [];
            while (true) {
                $isMore = count($whereIns) > 0;
                $typeWhereIn = $this->choice(
                    "Do you want to " . ($isMore ? "add more" : "use") . " where in clause conditions?",
                    [
                        1 => "No",
                        2 => "Yes",
                    ]
                );
                switch ($typeWhereIn) {
                    case "Yes":
                        $whereIns[] = $this->ask(
                            "Please provide the where in clause conditions (seperate with comma for column and value)"
                        );
                        break;
                    default:
                        break 2;
                }
            }
        }
        if ($whereIns != null) {
            foreach ($whereIns as $key => $whereIn) {
                $this->commands["where-in"] .= ($key > 0 ? " " : "") . "--where-in={$whereIn}";
            }
        }

        $whereInsFinal = [];
        foreach ($whereIns as $key => $where) {
            $result = $this->optionToArray($where);
            if (count($result) < 2) {
                throw new \Exception("You must provide atleast 2 values for where in clause");
            }
            $whereInsFinal[$key]["column"] = $result[0];
            unset($result[0]);
            $whereInsFinal[$key]["value"] = $result;
        }
        if (count($whereInsFinal) == 0) {
            unset($this->commands["where-in"]);
        }
        return $whereInsFinal;
    }

    private function checkIdsInput(): array
    {
        if ($this->option('all-ids')) {
            $this->commands["ids"] = "--all-ids";
            return [[], []];
        }
        $selectedIds = $this->option("ids");
        $ignoredIds = $this->option("ignore-ids");
        if ($selectedIds == null && $ignoredIds == null && $this->showPrompt) {
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
        if ($selectedIds != null) {
            $this->commands["ids"] = "--ids={$selectedIds}";
        }
        if ($ignoredIds != null) {
            $this->commands["ids"] = "--ignore-ids={$ignoredIds}";
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
            $this->commands["fields"] = "--all-fields";
            return [[], []];
        }
        $selectedFields = $this->option("fields");
        $ignoredFields = $this->option("ignore-fields");
        if ($selectedFields == null && $ignoredFields == null && $this->showPrompt) {
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
        if ($ignoredFields != null) {
            $this->commands["fields"] = "--ignore-fields={$ignoredFields}";
        }
        if ($selectedFields != null) {
            $this->commands["fields"] = "--fields={$selectedFields}";
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
            if ($relations == null && $this->showPrompt) {
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
            if ($relations != null) {
                $this->commands["relation"] = "--relations={$relations}";
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
