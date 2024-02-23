<?php
namespace TYGHaykal\LaravelSeedGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use TYGHaykal\LaravelSeedGenerator\Commands\Model\ModelCommand;
use TYGHaykal\LaravelSeedGenerator\Commands\Table\TableCommand;
use TYGHaykal\LaravelSeedGenerator\Helpers\StringHelper;
use TYGHaykal\LaravelSeedGenerator\Helpers\TableHelper;
use TYGHaykal\LaravelSeedGenerator\Traits\CommandTrait;

class SeedGeneratorCommand extends Command
{
    use CommandTrait;
    protected $signature = "seed:generate 
                                {--show-prompt} 
                                {--mode= : Set the resource mode (table or model)} 
                                {--model-mode : Set the resource mode to model} 
                                {--all-models : Generate seed for all models} 
                                {--models= : Generate seed for selected model} 
                                {--table-mode : Set the resource mode to table} 
                                {--tables= : Generate seed for selected tables}
                                {--all-tables : Generate seed for all tables}
                                {--without-relations} 
                                {--where-raw-query= : The raw query condition}
                                {--where=* : The where clause conditions}
                                {--where-in=* : The where in clause conditions}
                                {--where-not-in=* : The where in clause conditions}
                                {--order-by= : Order data to be seeded} 
                                {--limit= : Limit data to be seeded} 
                                {--all-ids} 
                                {--ids= : The ids to be seeded} 
                                {--ignore-ids= : The ids to be ignored} 
                                {--all-fields} 
                                {--fields= : The fields to be seeded} 
                                {--ignore-fields= : The fields to be ignored} 
                                {--relations= : The relations to be seeded}
                                {--relations-limit= : Limit relation data to be seeded}
                                {--output= : Output file will be located on this path} 
                                {--no-seed : Skip update the database seeder file}
                                ";

    protected $description = "Generate a seed file from a model";
    private $oldLaravelVersion = false,
        $commands = [],
        $showPrompt = false;

    public function handle(Filesystem $files, Repository $config)
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

                    return (new TableCommand($this, $files, $config))->handle();

                case 'model':
                    $this->checkModelInput()
                        ->checkWhereRawQueryInput()
                        ->checkWhereInput()
                        ->checkWhereInInput()
                        ->checkWhereNotInInput()
                        ->checkOrderByInput()
                        ->checkLimitInput()
                        ->checkIdsInput()
                        ->checkFieldsInput()
                        ->checkRelationInput()
                        ->checkRelationLimitInput()
                        ->checkOutputLocationInput();

                    return (new ModelCommand($this, $files, $config))->handle();

                default:
                    throw new \Exception("Mode $mode not supported, only 'table' and 'model' are supported");
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
}
