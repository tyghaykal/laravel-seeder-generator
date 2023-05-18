<?php
namespace TYGHaykal\LaravelSeedGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use TYGHaykal\LaravelSeedGenerator\Helpers\StringHelper;

class SeedGeneratorCommand extends Command
{
    protected $signature = "seed:generate {model} {--ids= : The ids to be seeded} {--ignore-ids= : The ids to be ignored} {--fields= : The fields to be seeded} {--ignore-fields= : The fields to be ignored}";
    protected $description = "Generate a seed file from a model";

    public function handle(Filesystem $files)
    {
        try {
            $modelPath = $this->checkModel($this->argument("model"));
            $modelInstance = app($modelPath);

            $selectedIds = $this->optionToArray($this->option("ids"));
            $ignoreIds = $this->optionToArray($this->option("ignore-ids"));

            if (count($selectedIds) > 0 && count($ignoreIds) > 0) {
                throw new \Exception(
                    "You can't use --ignore-fields and --selected-fields at the same time."
                );
            }

            $selectedFields = $this->optionToArray($this->option("fields"));
            $ignoreFields = $this->optionToArray(
                $this->option("ignore-fields")
            );

            if (count($ignoreFields) > 0 && count($selectedFields) > 0) {
                throw new \Exception(
                    "You can't use --ignore-fields and --selected-fields at the same time."
                );
            }

            $seederCommands = $this->getSeederCode(
                $modelInstance,
                $selectedIds,
                $ignoreIds,
                $selectedFields,
                $ignoreFields
            );

            $this->writeSeederFile($files, $seederCommands, $modelInstance);
        } catch (\Exception $e) {
            dump($e->getMessage());
            $this->error($e->getMessage());
            return 1;
        }
    }

    private function checkModel(string $model): string
    {
        //check if model name is provided
        if (!$model) {
            throw new \Exception(
                "Please provide a model name using the --model option."
            );
        }

        //check if model file exists in \App\Models
        $modelPath = "\\App\\Models\\{$model}";
        if (!class_exists($modelPath)) {
            $modelPath = "\\App\\{$model}.php";
            if (class_exists($modelPath)) {
                return "\\App\\$model";
            }
            throw new \Exception("Model file not found at {$modelPath}");
        }
        return "\\App\\Models\\$model";
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
        array $ignoreFields
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
                $dataArray = array_intersect_key(
                    $dataArray,
                    array_flip($selectedFields)
                );
            }
            if (count($ignoreFields) > 0) {
                //return all fields except the ignored fields
                $dataArray = array_diff_key(
                    $dataArray,
                    array_flip($ignoreFields)
                );
            }
            $dataArray = StringHelper::prettyPrintArray($dataArray, 3);

            // Replace array () into []
            $dataArray = str_replace(["array (", ")"], ["[", "]"], $dataArray);

            // Remove trailing comma
            $dataArray = rtrim($dataArray, ",]") . "]";

            $code =
                "\$newData$key = \\" .
                get_class($modelInstance->getModel()) .
                "::create(" .
                $dataArray .
                ");";

            if ($key != 0) {
                $code = StringHelper::generateIndentation($code, 2);
            }

            $codes[] = $code;
        }
        $code = implode("\n", $codes);
        //remove tab from $code
        $code = str_replace("\t", "", $code);
        $code = str_replace("\r", "", $code);
        return $code;
    }

    private function writeSeederFile(
        Filesystem $files,
        string $code,
        Model $modelInstance
    ): void {
        $isReplace = false;
        //get $modelInstance namespace
        $modelNamespace = get_class($modelInstance->getModel());
        $modelNamespace = str_replace("App\Models\\", "", $modelNamespace);
        $modelNamespace = str_replace("App\\", "", $modelNamespace);
        $filePath = database_path("seeders/{$modelNamespace}Seeder.php");

        if ($files->exists($filePath)) {
            $isReplace = true;
            $files->delete($filePath);
        }

        //get $modelInstance class name
        $seedClassName = class_basename($modelInstance->getModel());
        // Changed the suffix to 'Seeder'
        $seedClassName = Str::studly($seedClassName) . "Seeder";

        $seedNamespace = class_basename($modelInstance->getModel());
        $seedNamespace = str_replace("\\$seedNamespace", "", $seedNamespace);
        $seedNamespace = str_replace("$seedNamespace", "", $seedNamespace);
        if ($seedNamespace != "") {
            $seedNamespace .= "\\{$seedNamespace}";
        }

        $stubContent = $files->get(__DIR__ . "/../Stubs/Seed.stub");
        $fileContent = str_replace(
            ["{{ namespace }}", "{{ class }}", "{{ code }}"],
            [$seedNamespace, $seedClassName, $code],
            $stubContent
        );
        $files->put($filePath, $fileContent);

        $this->info(
            ($isReplace ? "Seed file replaced" : "Seed file created") .
                " : {$filePath}"
        );
    }
}
