<?php
namespace TYGHaykal\LaravelSeedGenerator\Tests;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use TYGHaykal\LaravelSeedGenerator\SeedGeneratorServiceProvider;
use TYGHaykal\LaravelSeedGenerator\Commands\SeedGeneratorCommand;
use TYGHaykal\LaravelSeedGenerator\Tests\Database\Seeders\TestModelSeeder;

class ModelCommandTest extends TestCase
{
    use RefreshDatabase;
    protected function getPackageProviders($app)
    {
        return [SeedGeneratorServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app["config"]->set("database.default", "testing");

        $app["config"]->set("app.aliases", [
            "TestModel" => \App\Models\TestModel::class,
            "TestModelChild" => \App\Models\TestModelChild::class,
        ]);
    }

    private $folderResult = false,
        $folderSeeder = "",
        $beforeLaravel7 = false;
    public function setUp(): void
    {
        parent::setUp();
        $this->folderResult = version_compare(app()->version(), "8.0.0") >= 0 ? "After8" : "Before8";
        $this->folderSeeder = version_compare(app()->version(), "8.0.0") >= 0 ? "seeders" : "seeds";
        $this->beforeLaravel7 = version_compare(app()->version(), "7.0.0") < 0;
        $this->loadMigrationsFrom(__DIR__ . "/database/migrations");
        // copy database\DatabaseSeeder.php to orchestra database folder
        if (!File::exists(database_path($this->folderSeeder))) {
            File::makeDirectory(database_path($this->folderSeeder));
        }

        File::copy(__DIR__ . "/database/DatabaseSeeder.php", database_path($this->folderSeeder . "\DatabaseSeeder.php"));
    }

    public function test_seed_generator_error_no_mode_inserted()
    {
        $this->artisan("seed:generate")
            ->expectsQuestion("Please provide the mode", "")
            ->assertExitCode(1);
    }

    public function test_seed_generator_error_no_model_inserted()
    {
        $this->artisan("seed:generate --model-mode")
            ->expectsQuestion("Please provide a model name and separate with comma for multiple models", "")
            ->assertExitCode(1);
    }

    public function test_seed_generator_error_not_existing_model()
    {
        $model = "ASDZXC";
        $this->artisan("seed:generate --model-mode --models=$model")->assertExitCode(1);

        // now check with ask method
        $this->artisan("seed:generate --model-mode")
            ->expectsQuestion("Please provide a model name and separate with comma for multiple models", $model)
            ->assertExitCode(1);
    }

    public function test_seed_generator_error_send_selected_fields_and_ignored_fields_in_same_time()
    {
        $model = "TestModel";
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--fields" => "id,name",
            "--ignore-fields" => "id,name",
        ])->assertExitCode(1);
    }

    public function test_seed_generator_success_full_with_no_additional_inline()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);

        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
        ])->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultAll.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_full_with_no_additional_prompt()
    {
        if ($this->beforeLaravel7) {
            $this->markTestSkipped("This test is not supported on Laravel < 8");
        }
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--show-prompt" => true,
        ])
            ->expectsChoice("Do you want to use where raw query clause condition?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where not in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use order by in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to select or ignore ids?", "Select all", [
                "Select all",
                "Select some ids",
                "Ignore some ids",
            ])
            ->expectsChoice("Do you want to select or ignore fields?", "Select all", [
                "Select all",
                "Select some fields",
                "Ignore some fields",
            ])
            ->expectsChoice("Do you want to seed the has-many relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to change the output location?", "No", ["No", "Yes"])
            ->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultAll.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_where_raw_query_clause_inline()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--where-raw-query" => "id > 1 AND id < 3",
        ])->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultWhereRawQuery.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_where_raw_query_clause_prompt()
    {
        if ($this->beforeLaravel7) {
            $this->markTestSkipped("This test is not supported on Laravel < 8");
        }
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--show-prompt" => true,
        ])
            ->expectsChoice("Do you want to use where raw query clause condition?", "Yes", ["No", "Yes"])
            ->expectsQuestion("Please provide the where raw query condition", "id > 1 AND id < 3")
            ->expectsChoice("Do you want to use where clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where not in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use order by in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to select or ignore ids?", "Select all", [
                "Select all",
                "Select some ids",
                "Ignore some ids",
            ])
            ->expectsChoice("Do you want to select or ignore fields?", "Select all", [
                "Select all",
                "Select some fields",
                "Ignore some fields",
            ])
            ->expectsChoice("Do you want to seed the has-many relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to change the output location?", "No", ["No", "Yes"])
            ->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultWhereRawQuery.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_where_clause_inline()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--where" => ["id,=,1"],
        ])->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultWhere.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_where_clause_prompt()
    {
        if ($this->beforeLaravel7) {
            $this->markTestSkipped("This test is not supported on Laravel < 8");
        }
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--show-prompt" => true,
        ])
            ->expectsChoice("Do you want to use where raw query clause condition?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where clause conditions?", "Yes", ["No", "Yes"])
            ->expectsQuestion(
                "Please provide the where clause conditions (seperate with comma for column, type and value)",
                "id,=,1"
            )
            ->expectsChoice("Do you want to add more where clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where not in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use order by in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to select or ignore ids?", "Select all", [
                "Select all",
                "Select some ids",
                "Ignore some ids",
            ])
            ->expectsChoice("Do you want to select or ignore fields?", "Select all", [
                "Select all",
                "Select some fields",
                "Ignore some fields",
            ])
            ->expectsChoice("Do you want to seed the has-many relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to change the output location?", "No", ["No", "Yes"])
            ->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultWhere.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_where_in_clause_inline()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--where-in" => ["id,1,2"],
        ])->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultWhereIn.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_where_in_clause_prompt()
    {
        if ($this->beforeLaravel7) {
            $this->markTestSkipped("This test is not supported on Laravel < 8");
        }
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--show-prompt" => true,
        ])
            ->expectsChoice("Do you want to use where raw query clause condition?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where in clause conditions?", "Yes", ["No", "Yes"])
            ->expectsQuestion(
                "Please provide the where in clause conditions (seperate with comma for column and value)",
                "id,1,2"
            )
            ->expectsChoice("Do you want to add more where in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where not in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use order by in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to select or ignore ids?", "Select all", [
                "Select all",
                "Select some ids",
                "Ignore some ids",
            ])
            ->expectsChoice("Do you want to select or ignore fields?", "Select all", [
                "Select all",
                "Select some fields",
                "Ignore some fields",
            ])
            ->expectsChoice("Do you want to seed the has-many relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to change the output location?", "No", ["No", "Yes"])
            ->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultWhereIn.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_where_not_in_clause_inline()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--where-not-in" => ["id,1,2"],
        ])->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultWhereNotIn.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_where_not_in_clause_prompt()
    {
        if ($this->beforeLaravel7) {
            $this->markTestSkipped("This test is not supported on Laravel < 8");
        }
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--show-prompt" => true,
        ])
            ->expectsChoice("Do you want to use where raw query clause condition?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where not in clause conditions?", "Yes", ["No", "Yes"])
            ->expectsQuestion(
                "Please provide the where not in clause conditions (seperate with comma for column and value)",
                "id,1,2"
            )
            ->expectsChoice("Do you want to add more where not in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use order by in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to select or ignore ids?", "Select all", [
                "Select all",
                "Select some ids",
                "Ignore some ids",
            ])
            ->expectsChoice("Do you want to select or ignore fields?", "Select all", [
                "Select all",
                "Select some fields",
                "Ignore some fields",
            ])
            ->expectsChoice("Do you want to seed the has-many relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to change the output location?", "No", ["No", "Yes"])
            ->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultWhereNotIn.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_order_by_clause_inline()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--order-by" => "id,desc",
        ])->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultOrderBy.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_order_by_clause_prompt()
    {
        if ($this->beforeLaravel7) {
            $this->markTestSkipped("This test is not supported on Laravel < 8");
        }
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--show-prompt" => true,
        ])
            ->expectsChoice("Do you want to use where raw query clause condition?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where not in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use order by in seeded data?", "Yes", ["No", "Yes"])
            ->expectsQuestion("Please provide the order by of data to be seeded", "id,desc")
            ->expectsChoice("Do you want to use limit in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to select or ignore ids?", "Select all", [
                "Select all",
                "Select some ids",
                "Ignore some ids",
            ])
            ->expectsChoice("Do you want to select or ignore fields?", "Select all", [
                "Select all",
                "Select some fields",
                "Ignore some fields",
            ])
            ->expectsChoice("Do you want to seed the has-many relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to change the output location?", "No", ["No", "Yes"])
            ->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultOrderBy.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_limit_inline()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--limit" => 1,
        ])->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultLimit.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_limit_clause_prompt()
    {
        if ($this->beforeLaravel7) {
            $this->markTestSkipped("This test is not supported on Laravel < 8");
        }
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--show-prompt" => true,
        ])
            ->expectsChoice("Do you want to use where raw query clause condition?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where not in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use order by in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in seeded data?", "Yes", ["No", "Yes"])
            ->expectsQuestion("Please provide the limit of data to be seeded", 1)
            ->expectsChoice("Do you want to select or ignore ids?", "Select all", [
                "Select all",
                "Select some ids",
                "Ignore some ids",
            ])
            ->expectsChoice("Do you want to select or ignore fields?", "Select all", [
                "Select all",
                "Select some fields",
                "Ignore some fields",
            ])
            ->expectsChoice("Do you want to seed the has-many relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to change the output location?", "No", ["No", "Yes"])
            ->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultLimit.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_selected_id_inline()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--ids" => "1,2",
        ])->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultSelectedIds.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }
    public function test_seed_generator_success_on_selected_id_prompt()
    {
        if ($this->beforeLaravel7) {
            $this->markTestSkipped("This test is not supported on Laravel < 8");
        }
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--show-prompt" => true,
        ])
            ->expectsChoice("Do you want to use where raw query clause condition?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where not in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use order by in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to select or ignore ids?", "Select some ids", [
                "Select all",
                "Select some ids",
                "Ignore some ids",
            ])
            ->expectsQuestion("Please provide the ids you want to select (seperate with comma)", "1,2")
            ->expectsChoice("Do you want to select or ignore fields?", "Select all", [
                "Select all",
                "Select some fields",
                "Ignore some fields",
            ])
            ->expectsChoice("Do you want to seed the has-many relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to change the output location?", "No", ["No", "Yes"])
            ->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultSelectedIds.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_ignored_id_inline()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--ignore-ids" => "1,2",
        ])->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultIgnoreIds.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_ignored_id_prompt()
    {
        if ($this->beforeLaravel7) {
            $this->markTestSkipped("This test is not supported on Laravel < 8");
        }
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--show-prompt" => true,
        ])
            ->expectsChoice("Do you want to use where raw query clause condition?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where not in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use order by in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to select or ignore ids?", "Ignore some ids", [
                "Select all",
                "Select some ids",
                "Ignore some ids",
            ])
            ->expectsQuestion("Please provide the ids you want to ignore (seperate with comma)", "1,2")
            ->expectsChoice("Do you want to select or ignore fields?", "Select all", [
                "Select all",
                "Select some fields",
                "Ignore some fields",
            ])
            ->expectsChoice("Do you want to seed the has-many relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to change the output location?", "No", ["No", "Yes"])
            ->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultIgnoreIds.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_selected_fields_inline()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--fields" => "id,name",
        ])->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultSelectedField.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_selected_fields_prompt()
    {
        if ($this->beforeLaravel7) {
            $this->markTestSkipped("This test is not supported on Laravel < 8");
        }
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--show-prompt" => true,
        ])
            ->expectsChoice("Do you want to use where raw query clause condition?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where not in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use order by in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to select or ignore ids?", "Select all", [
                "Select all",
                "Select some ids",
                "Ignore some ids",
            ])
            ->expectsChoice("Do you want to select or ignore fields?", "Select some fields", [
                "Select all",
                "Select some fields",
                "Ignore some fields",
            ])
            ->expectsQuestion("Please provide the fields you want to select (seperate with comma)", "id,name")
            ->expectsChoice("Do you want to seed the has-many relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to change the output location?", "No", ["No", "Yes"])
            ->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultSelectedField.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_ignored_fields_inline()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--ignore-fields" => "id,name",
        ])->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultIgnoredField.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }
    public function test_seed_generator_success_on_ignored_fields_prompt()
    {
        if ($this->beforeLaravel7) {
            $this->markTestSkipped("This test is not supported on Laravel < 8");
        }
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--show-prompt" => true,
        ])
            ->expectsChoice("Do you want to use where raw query clause condition?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where not in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use order by in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to select or ignore ids?", "Select all", [
                "Select all",
                "Select some ids",
                "Ignore some ids",
            ])
            ->expectsChoice("Do you want to select or ignore fields?", "Ignore some fields", [
                "Select all",
                "Select some fields",
                "Ignore some fields",
            ])
            ->expectsQuestion("Please provide the fields you want to ignore (seperate with comma)", "id,name")
            ->expectsChoice("Do you want to seed the has-many relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to change the output location?", "No", ["No", "Yes"])
            ->assertExitCode(0);
        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultIgnoredField.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_relations_inline()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--relations" => "test_model_childs",
        ])->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultRelation.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_relations_prompt()
    {
        if ($this->beforeLaravel7) {
            $this->markTestSkipped("This test is not supported on Laravel < 8");
        }
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--show-prompt" => true,
        ])
            ->expectsChoice("Do you want to use where raw query clause condition?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where not in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use order by in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to select or ignore ids?", "Select all", [
                "Select all",
                "Select some ids",
                "Ignore some ids",
            ])
            ->expectsChoice("Do you want to select or ignore fields?", "Select all", [
                "Select all",
                "Select some fields",
                "Ignore some fields",
            ])
            ->expectsChoice("Do you want to seed the has-many relation?", "Yes", ["No", "Yes"])
            ->expectsQuestion("Please provide the has-many relations you want to seed (seperate with comma)", "test_model_childs")
            ->expectsChoice("Do you want to use limit in relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to change the output location?", "No", ["No", "Yes"])
            ->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultRelation.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_relations_limit_inline()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--relations" => "test_model_childs",
            "--relations-limit" => 1,
        ])->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultRelationLimit.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );

        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_relations_limit_prompt()
    {
        if ($this->beforeLaravel7) {
            $this->markTestSkipped("This test is not supported on Laravel < 8");
        }
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--show-prompt" => true,
        ])
            ->expectsChoice("Do you want to use where raw query clause condition?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where not in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use order by in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to select or ignore ids?", "Select all", [
                "Select all",
                "Select some ids",
                "Ignore some ids",
            ])
            ->expectsChoice("Do you want to select or ignore fields?", "Select all", [
                "Select all",
                "Select some fields",
                "Ignore some fields",
            ])
            ->expectsChoice("Do you want to seed the has-many relation?", "Yes", ["No", "Yes"])
            ->expectsQuestion("Please provide the has-many relations you want to seed (seperate with comma)", "test_model_childs")
            ->expectsChoice("Do you want to use limit in relation?", "Yes", ["No", "Yes"])
            ->expectsQuestion("Please provide the limit of relation data to be seeded", "1")
            ->expectsChoice("Do you want to change the output location?", "No", ["No", "Yes"])
            ->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultRelationLimit.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_output_file_location_inline()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--output" => "Should/Be/In/Here/Data",
        ])->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/Should/Be/In/Here/Data/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultWithOutputLocation.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/Should/Be/In/Here/Data/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_output_file_location_prompt()
    {
        if ($this->beforeLaravel7) {
            $this->markTestSkipped("This test is not supported on Laravel < 8");
        }
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => $model,
            "--show-prompt" => true,
        ])
            ->expectsChoice("Do you want to use where raw query clause condition?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where not in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use order by in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to select or ignore ids?", "Select all", [
                "Select all",
                "Select some ids",
                "Ignore some ids",
            ])
            ->expectsChoice("Do you want to select or ignore fields?", "Select all", [
                "Select all",
                "Select some fields",
                "Ignore some fields",
            ])
            ->expectsChoice("Do you want to seed the has-many relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in relation?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to change the output location?", "Yes", ["No", "Yes"])
            ->expectsQuestion("Please provide the output location", "Should/Be/In/Here/Data")
            ->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/Should/Be/In/Here/Data/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/ResultWithOutputLocation.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/Should/Be/In/Here/Data/TestModelSeeder.php"))
        );
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_multiple_models_inline()
    {
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => "TestModel,TestModelChild",
        ])->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/MultipleModelResult/TestModelSeeder.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );

        $this->assertSame($expectedOutput, $actualOutput);

        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelChildSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(
                __DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/MultipleModelResult/TestModelChildSeeder.txt"
            )
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelChildSeeder.php"))
        );

        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_multiple_models_prompt()
    {
        if ($this->beforeLaravel7) {
            $this->markTestSkipped("This test is not supported on Laravel < 8");
        }

        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "--model-mode" => true,
            "--models" => "TestModel,TestModelChild",
            "--show-prompt" => true,
        ])
            ->expectsChoice("Do you want to use where raw query clause condition?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use where not in clause conditions?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use order by in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to use limit in seeded data?", "No", ["No", "Yes"])
            ->expectsChoice("Do you want to select or ignore ids?", "Select all", [
                "Select all",
                "Select some ids",
                "Ignore some ids",
            ])
            ->expectsChoice("Do you want to select or ignore fields?", "Select all", [
                "Select all",
                "Select some fields",
                "Ignore some fields",
            ])
            ->expectsChoice("Do you want to change the output location?", "No", ["No", "Yes"])
            ->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/MultipleModelResult/TestModelSeeder.txt")
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelSeeder.php"))
        );

        $this->assertSame($expectedOutput, $actualOutput);

        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/Models/TestModelChildSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(
                __DIR__ . "/ExpectedResult/ModelMode/{$this->folderResult}/MultipleModelResult/TestModelChildSeeder.txt"
            )
        );
        $actualOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(database_path("{$this->folderSeeder}/Models/TestModelChildSeeder.php"))
        );

        $this->assertSame($expectedOutput, $actualOutput);
    }
}
