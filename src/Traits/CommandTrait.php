<?php
namespace TYGHaykal\LaravelSeedGenerator\Traits;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

trait CommandTrait
{
    private $selectedTables = [],
        $runCommands = [],
        $wheres = [],
        $whereIns = [],
        $whereNotIns = [],
        $orderBy = [],
        $selectedIds = [],
        $ignoredIds = [],
        $selectedFields = [],
        $ignoredFields = [],
        $relations = [],
        $limit = null,
        $outputLocation = null,
        $rawQuery = null,
        $models = [],
        $relationLimits = null;

    public function isOldLaravelVersion()
    {
        return version_compare(app()->version(), "8.0.0") < 0;
    }

    public function getRunCommand()
    {
        $runCommand = "artisan seed:generate ";
        $runCommand .= implode(" ", $this->runCommands);
        return $runCommand;
    }

    public function getUpdateDatabaseSeeder()
    {
        return !$this->option("no-seed");
    }

    public function getMode(): string
    {
        $mode = "";
        if ($this->option('table-mode')) {
            $this->runCommands["mode"] = "--table-mode";
            $mode = "table";
        } elseif ($this->option('model-mode')) {
            $mode = "model";
            $this->runCommands["mode"] = "--model-mode";
        } else {
            $mode = $this->option("mode");
            if (!$mode) {
                $mode = $this->choice("Please provide the mode", ["table", "model"]);
            }
            $this->runCommands["mode"] = "--mode={$mode}";
        }

        return $mode;
    }

    public function checkModelInput(): self
    {
        $models = $this->option("models");

        if (!$models) {
            $models = $this->ask("Please provide a model name and separate with comma for multiple models");
        }
        $this->runCommands["model"] = "--models=" . $models;

        $models = $this->optionToArray($models);
        if (count($models) == 0) {
            throw new \Exception("You must provide at least one model name");
        }

        foreach ($models as $model) {
            $modelPath = "\\App\\Models\\{$model}";
            $modelFound = false;
            if (class_exists($modelPath)) {
                $this->models[] = "\\App\\Models\\$model";
                $modelFound = true;
            } else {
                $modelPath = "\\App\\{$model}";
                if (class_exists($modelPath)) {
                    $this->models[] = "\\App\\$model";
                    $modelFound = true;
                }
            }

            if (!$modelFound) {
                throw new \Exception("Model file not found at namespace \App\Models or \App");
            }
        }
        return $this;
    }

    public function getModels(): array
    {
        return $this->models;
    }

    public function getModelInstance(string $model): Model
    {
        return app($model);
    }

    public function checkSelectedTableInput(): self
    {
        $selectedTables = $this->option('tables');
        $isAllTables = $this->option('all-tables');

        if ($isAllTables) {
            $this->runCommands["all-tables"] = "--all-tables";
            return $this;
        }

        if (!$selectedTables) {
            $selectedTables = $this->ask("Please provide the tables names? (comma separated)");
        }

        if (!$selectedTables) {
            throw new \Exception("You must provide at least one table name");
        }

        if ($selectedTables) {
            $this->selectedTables = explode(',', $selectedTables);
        }

        $this->runCommands["tables"] = "--tables={$selectedTables}";

        return $this;
    }

    public function getSelectedTables(): array
    {
        return $this->selectedTables;
    }

    private function optionToArray(?string $ids): array
    {
        if (!$ids) {
            return [];
        }
        $ids = explode(",", $ids);
        return $ids;
    }

    private function checkWhereRawQueryInput(): self
    {
        $whereRawQuery = $this->option("where-raw-query");
        if ($whereRawQuery == null && $this->showPrompt) {
            $typeRawQuery = $this->choice("Do you want to use where raw query clause condition?", [
                1 => "No",
                2 => "Yes",
            ]);
            switch ($typeRawQuery) {
                case "Yes":
                    $whereRawQuery = $this->ask("Please provide the where raw query condition");
                    break;
            }
        }
        if ($whereRawQuery != null) {
            $this->runCommands["where-raw-query"] = "--where-raw-query='{$whereRawQuery}'";
        }
        $this->whereRawQuery = $whereRawQuery;
        return $this;
    }

    public function getWhereRawQuery(): ?string
    {
        return $this->whereRawQuery;
    }

    private function checkWhereInput(): self
    {
        $wheres = $this->option("where");
        $this->runCommands["where"] = "";
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
                            "Please provide the where clause conditions (seperate with comma for column, type and value)"
                        );
                        break;
                    default:
                        break 2;
                }
            }
        }
        if ($wheres != null) {
            foreach ($wheres as $key => $where) {
                $this->runCommands["where"] .= ($key > 0 ? " " : "") . "--where={$where}";
            }
        }
        $wheresFinal = [];
        foreach ($wheres as $key => $where) {
            $result = $this->optionToArray($where);
            if (count($result) != 3) {
                throw new \Exception("You must provide 3 values for where clause (column, type, value)");
            }
            $wheresFinal[$key]["column"] = $result[0];
            $wheresFinal[$key]["type"] = $result[1];
            $wheresFinal[$key]["value"] = $result[2];
        }
        if (count($wheresFinal) == 0) {
            unset($this->runCommands["where"]);
        }

        $this->wheres = $wheresFinal;
        return $this;
    }

    public function getWheres(): array
    {
        return $this->wheres;
    }

    private function checkWhereInInput(): self
    {
        $whereIns = $this->option("where-in");
        $this->runCommands["where-in"] = "";
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
                $this->runCommands["where-in"] .= ($key > 0 ? " " : "") . "--where-in={$whereIn}";
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
            unset($this->runCommands["where-in"]);
        }

        $this->whereIns = $whereInsFinal;

        return $this;
    }

    public function getWhereIns(): array
    {
        return $this->whereIns;
    }

    private function checkWhereNotInInput(): self
    {
        $whereNotIn = $this->option("where-not-in");
        $this->runCommands["where-not-in"] = "";
        if (count($whereNotIn) == 0 && $this->showPrompt) {
            $whereNotIn = [];
            while (true) {
                $isMore = count($whereNotIn) > 0;
                $typeWhereIn = $this->choice(
                    "Do you want to " . ($isMore ? "add more" : "use") . " where not in clause conditions?",
                    [
                        1 => "No",
                        2 => "Yes",
                    ]
                );
                switch ($typeWhereIn) {
                    case "Yes":
                        $whereNotIn[] = $this->ask(
                            "Please provide the where not in clause conditions (seperate with comma for column and value)"
                        );
                        break;
                    default:
                        break 2;
                }
            }
        }
        if ($whereNotIn != null) {
            foreach ($whereNotIn as $key => $whereIn) {
                $this->runCommands["where-not-in"] .= ($key > 0 ? " " : "") . "--where-not-in={$whereIn}";
            }
        }
        $whereNotInFinal = [];
        foreach ($whereNotIn as $key => $where) {
            $result = $this->optionToArray($where);
            if (count($result) < 2) {
                throw new \Exception("You must provide atleast 2 values for where not in clause");
            }
            $whereNotInFinal[$key]["column"] = $result[0];
            unset($result[0]);
            $whereNotInFinal[$key]["value"] = $result;
        }
        if (count($whereNotInFinal) == 0) {
            unset($this->runCommands["where-not-in"]);
        }
        $this->whereNotIns = $whereNotInFinal;
        return $this;
    }

    public function getWhereNotIns(): array
    {
        return $this->whereNotIns;
    }

    private function checkOrderByInput(): self
    {
        $orderBy = $this->option("order-by");
        if ($orderBy == null && $this->showPrompt) {
            $typeLimit = $this->choice("Do you want to use order by in seeded data?", [
                1 => "No",
                2 => "Yes",
            ]);
            switch ($typeLimit) {
                case "Yes":
                    $orderBy = $this->ask("Please provide the order by of data to be seeded");
                    break;
            }
        }
        if ($orderBy != null) {
            $this->runCommands["order-by"] = "--order-by={$orderBy}";
        }

        $orderBy = $this->optionToArray($orderBy);
        if (count($orderBy) == 2) {
            $this->orderBy = [
                "column" => $orderBy[0],
                "direction" => $orderBy[1],
            ];
        }

        return $this;
    }

    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    private function checkLimitInput(): self
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
            $this->runCommands["limit"] = "--limit={$limit}";
        }
        $this->limit = $limit;
        return $this;
    }

    public function getLimit(): ?string
    {
        return $this->limit;
    }

    private function checkIdsInput(): self
    {
        if ($this->option('all-ids')) {
            $this->runCommands["ids"] = "--all-ids";
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
            $this->runCommands["ids"] = "--ids={$selectedIds}";
        }
        if ($ignoredIds != null) {
            $this->runCommands["ids"] = "--ignore-ids={$ignoredIds}";
        }
        $selectedIds = $this->optionToArray($selectedIds);
        $ignoredIds = $this->optionToArray($ignoredIds);
        if (count($selectedIds) > 0 && count($ignoredIds) > 0) {
            throw new \Exception("You can't use --ignore-ids and --ids at the same time.");
        }

        $this->selectedIds = $selectedIds;
        $this->ignoredIds = $ignoredIds;
        return $this;
    }

    public function getSelectedIds(): array
    {
        return $this->selectedIds;
    }

    public function getIgnoredIds(): array
    {
        return $this->ignoredIds;
    }

    private function checkFieldsInput(): self
    {
        if ($this->option('all-fields')) {
            $this->runCommands["fields"] = "--all-fields";
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
            $this->runCommands["fields"] = "--ignore-fields={$ignoredFields}";
        }
        if ($selectedFields != null) {
            $this->runCommands["fields"] = "--fields={$selectedFields}";
        }
        $selectedFields = $this->optionToArray($selectedFields);
        $ignoredFields = $this->optionToArray($ignoredFields);
        if (count($selectedFields) > 0 && count($ignoredFields) > 0) {
            throw new \Exception("You can't use --ignore-fields and --fields at the same time.");
        }

        $this->selectedFields = $selectedFields;
        $this->ignoredFields = $ignoredFields;
        return $this;
    }

    public function getSelectedFields(): array
    {
        return $this->selectedFields;
    }

    public function getIgnoredFields(): array
    {
        return $this->ignoredFields;
    }

    private function checkOutputLocationInput(): self
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
            $this->runCommands["output"] = "--output={$outputLocation}";
        }
        $this->outputLocation = $outputLocation;
        return $this;
    }

    private function checkRelationInput(): self
    {
        if (count($this->models) > 1) {
            $this->info("Multiple models detected, relations will be ignored");
            return $this;
        }
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
                $this->runCommands["relation"] = "--relations={$relations}";
            }
            $relations = $this->optionToArray($relations);
            $this->relations = $relations;
        }
        return $this;
    }

    public function getRelations(): array
    {
        return $this->relations;
    }

    private function checkRelationLimitInput(): self
    {
        if (count($this->models) > 1) {
            $this->info("Multiple models detected, relations limit will be ignored");
            return $this;
        }

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
            $this->runCommands["relations-limit"] = "--relations-limit={$limit}";
        }

        $this->relationLimits = $limit;
        return $this;
    }

    public function getRelationLimits(): ?string
    {
        return $this->relationLimits;
    }

    public function getOutputLocation(): ?string
    {
        return $this->outputLocation;
    }

    public function getDatabaseConnection(): string
    {
        return config("database.default");
    }
}
