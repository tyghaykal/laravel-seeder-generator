<?php
namespace TYGHaykal\LaravelSeedGenerator\Tests\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TestModel;

class TestModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        TestModel::create([
            "name" => "test 1",
            "description" => "description 1",
            "created_at" => "2023-05-18T11:02:55.000000Z",
            "updated_at" => "2023-05-18T11:02:55.000000Z",
        ]);
        TestModel::create([
            "name" => "test 2",
            "description" => "description 2",
            "created_at" => "2023-05-18T11:02:55.000000Z",
            "updated_at" => "2023-05-18T11:02:55.000000Z",
        ]);
        TestModel::create([
            "name" => "test 3",
            "description" => "description 3",
            "created_at" => "2023-05-18T11:02:55.000000Z",
            "updated_at" => "2023-05-18T11:02:55.000000Z",
        ]);
    }
}
