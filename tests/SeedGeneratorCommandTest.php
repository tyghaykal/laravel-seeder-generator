<?php
namespace TYGHaykal\LaravelSeedGenerator\Tests;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use TYGHaykal\LaravelSeedGenerator\SeedGeneratorServiceProvider;
use TYGHaykal\LaravelSeedGenerator\Commands\SeedGeneratorCommand;
use TYGHaykal\LaravelSeedGenerator\Tests\Database\Seeders\TestModelSeeder;

class SeedGeneratorCommandTest extends TestCase
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
        ]);
    }

    private $folderResult = false,
        $folderSeeder = "",
        $oldLaravel = false;
    public function setUp(): void
    {
        parent::setUp();
        $this->folderResult = version_compare(app()->version(), "8.0.0") >= 0 ? "After8" : "Before8";
        $this->folderSeeder = version_compare(app()->version(), "8.0.0") >= 0 ? "seeders" : "seeds";
        $this->oldLaravel = version_compare(app()->version(), "7.0.0") < 0;
        $this->loadMigrationsFrom(__DIR__ . "/database/migrations");
    }

    /** @test */
    public function test_seed_generator_error_no_model_inserted()
    {
        $this->artisan("seed:generate")
            ->expectsQuestion("Please provide a model name", "")
            ->assertExitCode(1);
    }

    public function test_seed_generator_error_not_existing_model()
    {
        $model = "ASDZXC";
        $this->artisan("seed:generate $model")->assertExitCode(1);

        // now check with ask method
        $this->artisan("seed:generate")
            ->expectsQuestion("Please provide a model name", $model)
            ->assertExitCode(1);
    }

    public function test_seed_generator_error_send_selected_fields_and_ignored_fields_in_same_time()
    {
        $model = "TestModel";
        $this->artisan("seed:generate", [
            "model" => $model,
            "--fields" => "id,name",
            "--ignore-fields" => "id,name",
        ])->assertExitCode(1);
    }

    public function test_seed_generator_success_full_with_no_additional_inline()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "model" => $model,
            "--no-additional" => true,
        ])->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/{$this->folderResult}/ResultAll.txt")
        );
        $actualOutput = str_replace("\r\n", "\n", file_get_contents(database_path("{$this->folderSeeder}/TestModelSeeder.php")));
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_full_with_no_additional_asks()
    {
        if ($this->oldLaravel) {
            $this->markTestSkipped("This test is not supported on Laravel < 8");
        }
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "model" => $model,
        ])
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
            ->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/{$this->folderResult}/ResultAll.txt")
        );
        $actualOutput = str_replace("\r\n", "\n", file_get_contents(database_path("{$this->folderSeeder}/TestModelSeeder.php")));
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_selected_id_inline()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "model" => $model,
            "--ids" => "1,2",
            "--all-fields" => true,
            "--without-relations" => true,
        ])->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/{$this->folderResult}/ResultSelectedIds.txt")
        );
        $actualOutput = str_replace("\r\n", "\n", file_get_contents(database_path("{$this->folderSeeder}/TestModelSeeder.php")));
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }
    public function test_seed_generator_success_on_selected_id_asks()
    {
        if ($this->oldLaravel) {
            $this->markTestSkipped("This test is not supported on Laravel < 8");
        }
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "model" => $model,
        ])
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
            ->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/{$this->folderResult}/ResultSelectedIds.txt")
        );
        $actualOutput = str_replace("\r\n", "\n", file_get_contents(database_path("{$this->folderSeeder}/TestModelSeeder.php")));
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_ignored_id_inline()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "model" => $model,
            "--ignore-ids" => "1,2",
            "--all-fields" => true,
            "--without-relations" => true,
        ])->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/{$this->folderResult}/ResultIgnoreIds.txt")
        );
        $actualOutput = str_replace("\r\n", "\n", file_get_contents(database_path("{$this->folderSeeder}/TestModelSeeder.php")));
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_ignored_id_asks()
    {
        if ($this->oldLaravel) {
            $this->markTestSkipped("This test is not supported on Laravel < 8");
        }
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "model" => $model,
        ])
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
            ->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/{$this->folderResult}/ResultIgnoreIds.txt")
        );
        $actualOutput = str_replace("\r\n", "\n", file_get_contents(database_path("{$this->folderSeeder}/TestModelSeeder.php")));
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_selected_fields_inline()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "model" => $model,
            "--fields" => "id,name",
            "--without-relations" => true,
            "--all-ids" => true,
        ])->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/{$this->folderResult}/ResultSelectedField.txt")
        );
        $actualOutput = str_replace("\r\n", "\n", file_get_contents(database_path("{$this->folderSeeder}/TestModelSeeder.php")));
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_selected_fields_asks()
    {
        if ($this->oldLaravel) {
            $this->markTestSkipped("This test is not supported on Laravel < 8");
        }
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "model" => $model,
        ])
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
            ->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/{$this->folderResult}/ResultSelectedField.txt")
        );
        $actualOutput = str_replace("\r\n", "\n", file_get_contents(database_path("{$this->folderSeeder}/TestModelSeeder.php")));
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_ignored_fields_inline()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "model" => $model,
            "--ignore-fields" => "id,name",
            "--all-ids" => true,
            "--without-relations" => true,
        ])->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/{$this->folderResult}/ResultIgnoredField.txt")
        );
        $actualOutput = str_replace("\r\n", "\n", file_get_contents(database_path("{$this->folderSeeder}/TestModelSeeder.php")));
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }
    public function test_seed_generator_success_on_ignored_fields_asks()
    {
        if ($this->oldLaravel) {
            $this->markTestSkipped("This test is not supported on Laravel < 8");
        }
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "model" => $model,
        ])
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
            ->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/{$this->folderResult}/ResultIgnoredField.txt")
        );
        $actualOutput = str_replace("\r\n", "\n", file_get_contents(database_path("{$this->folderSeeder}/TestModelSeeder.php")));
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_relations_inline()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "model" => $model,
            "--all-fields" => true,
            "--all-ids" => true,
            "--relations" => "test_model_childs",
        ])->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/{$this->folderResult}/ResultRelation.txt")
        );
        $actualOutput = str_replace("\r\n", "\n", file_get_contents(database_path("{$this->folderSeeder}/TestModelSeeder.php")));
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function test_seed_generator_success_on_relations_asks()
    {
        if ($this->oldLaravel) {
            $this->markTestSkipped("This test is not supported on Laravel < 8");
        }
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "model" => $model,
        ])
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
            ->assertExitCode(0);

        // Now we should check if the file was created
        $this->assertTrue(File::exists(database_path("{$this->folderSeeder}/TestModelSeeder.php")));

        $expectedOutput = str_replace(
            "\r\n",
            "\n",
            file_get_contents(__DIR__ . "/ExpectedResult/{$this->folderResult}/ResultRelation.txt")
        );
        $actualOutput = str_replace("\r\n", "\n", file_get_contents(database_path("{$this->folderSeeder}/TestModelSeeder.php")));
        // dd($actualOutput);
        $this->assertSame($expectedOutput, $actualOutput);
    }
}
