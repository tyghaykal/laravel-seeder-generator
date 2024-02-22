<?php
namespace TYGHaykal\LaravelSeedGenerator\Traits;

use Illuminate\Console\Command;

trait CommandTrait
{
    private array $selectedTables = [];

    public function getMode(): string
    {
        if ($this->option('table-mode')) {
            return "table";
        } elseif ($this->option('model-mode')) {
            return "model";
        } else {
            $mode = $this->option("mode");
            if (!$mode) {
                return $this->choice("Please provide the mode?", ["table", "model"]);
            }
            return $mode;
        }
    }

    public function checkSelectedTableInput(): self
    {
        $selectedTables = $this->option('tables');

        if (!$selectedTables) {
            $selectedTables = $this->ask("Please provide the table names? (comma separated)");
        }

        if ($selectedTables) {
            $this->selectedTables = explode(',', $selectedTables);
        }

        return $this;
    }

    public function getSelectedTables(): array
    {
        return $this->selectedTables;
    }
}
