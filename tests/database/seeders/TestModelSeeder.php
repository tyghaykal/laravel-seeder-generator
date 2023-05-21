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
        $data0 = TestModel::create([
            "name" => "test 1",
            "description" => "description 1",
            "created_at" => \Carbon\Carbon::parse("2023-05-18T11:02:55.000000Z")->format("Y-m-d H:i:s"),
            "updated_at" => \Carbon\Carbon::parse("2023-05-18T11:02:55.000000Z")->format("Y-m-d H:i:s"),
        ]);
        //create data on relation test_model_childs
        $data0->test_model_childs()->createMany([
            [
                "test_model_id" => $data0->id,
                "name" => "child 1",
                "created_at" => \Carbon\Carbon::parse("2023-05-18T11:02:55.000000Z")->format("Y-m-d H:i:s"),
                "updated_at" => \Carbon\Carbon::parse("2023-05-18T11:02:55.000000Z")->format("Y-m-d H:i:s"),
            ],
        ]);

        $data1 = TestModel::create([
            "name" => "test 2",
            "description" => "description 2",
            "created_at" => \Carbon\Carbon::parse("2023-05-18T11:02:55.000000Z")->format("Y-m-d H:i:s"),
            "updated_at" => \Carbon\Carbon::parse("2023-05-18T11:02:55.000000Z")->format("Y-m-d H:i:s"),
        ]);

        $data1->test_model_childs()->createMany([
            [
                "test_model_id" => $data1->id,
                "name" => "child 2",
                "created_at" => \Carbon\Carbon::parse("2023-05-18T11:02:55.000000Z")->format("Y-m-d H:i:s"),
                "updated_at" => \Carbon\Carbon::parse("2023-05-18T11:02:55.000000Z")->format("Y-m-d H:i:s"),
            ],
        ]);

        $data2 = TestModel::create([
            "name" => "test 3",
            "description" => "description 3",
            "created_at" => \Carbon\Carbon::parse("2023-05-18T11:02:55.000000Z")->format("Y-m-d H:i:s"),
            "updated_at" => \Carbon\Carbon::parse("2023-05-18T11:02:55.000000Z")->format("Y-m-d H:i:s"),
        ]);

        $data2->test_model_childs()->createMany([
            [
                "test_model_id" => $data2->id,
                "name" => "child 3",
                "created_at" => \Carbon\Carbon::parse("2023-05-18T11:02:55.000000Z")->format("Y-m-d H:i:s"),
                "updated_at" => \Carbon\Carbon::parse("2023-05-18T11:02:55.000000Z")->format("Y-m-d H:i:s"),
            ],
        ]);
    }
}
