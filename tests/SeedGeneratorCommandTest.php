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

    private $folderResult = false;
    public function setUp(): void
    {
        parent::setUp();
        $this->folderResult = version_compare(app()->version(), "8.0.0") >= 0 ? "After8" : "Before8";
        $this->folderSeeder = version_compare(app()->version(), "8.0.0") >= 0 ? "seeders" : "seeds";
        $this->loadMigrationsFrom(__DIR__ . "/database/migrations");
    }

    /** @test */
    public function test_seed_generator_error_no_model_inserted()
    {
        $this->expectException(\Symfony\Component\Console\Exception\RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments (missing: "model").');

        $this->artisan("seed:generate");
    }

    public function test_seed_generator_error_not_existing_model()
    {
        $model = "ASDZXC";
        $this->artisan("seed:generate $model")->assertExitCode(1);
    }

    public function test_seed_generator_error_send_selected_ids_and_ignored_ids_in_same_time()
    {
        $model = "ASDZXC";
        $this->artisan("seed:generate", [
            "model" => $model,
            "--ids" => "1,2",
            "--ignore-ids" => "1,2",
        ])->assertExitCode(1);
    }

    public function test_seed_generator_error_send_selected_fields_and_ignored_fields_in_same_time()
    {
        $model = "ASDZXC";
        $this->artisan("seed:generate", [
            "model" => $model,
            "--fields" => "id,name",
            "--ignore-fields" => "id,name",
        ])->assertExitCode(1);
    }

    public function test_seed_generator_success()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate $model")->assertExitCode(0);

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

    public function test_seed_generator_success_on_selected_id()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "model" => $model,
            "--ids" => "1,2",
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

    public function test_seed_generator_success_on_ignored_id()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "model" => $model,
            "--ignore-ids" => "1,2",
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

    public function test_seed_generator_success_on_selected_fields()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "model" => $model,
            "--fields" => "id,name",
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

    public function test_seed_generator_success_on_ignored_fields()
    {
        $model = "TestModel";
        $this->seed(TestModelSeeder::class);
        $this->artisan("seed:generate", [
            "model" => $model,
            "--ignore-fields" => "id,name",
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
}
