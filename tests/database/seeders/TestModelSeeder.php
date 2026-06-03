<?php
namespace TYGHaykal\LaravelSeedGenerator\Tests\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TestModel;

class TestModelSeeder extends Seeder
{
    private function getFormattedTimestamp()
    {
        return \Carbon\Carbon::parse("2023-05-18T11:02:55.000000Z")
            ->format((new TestModel())->getDateFormat());
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $timestamp = $this->getFormattedTimestamp();

        $data0 = TestModel::create([
            "name" => "test 1",
            "description" => "description 1",
            "created_at" => $timestamp,
            "updated_at" => $timestamp,
        ]);
        //create data on relation test_model_childs
        $data0->test_model_childs()->createMany([
            [
                "test_model_id" => $data0->id,
                "name" => "child 1",
                "created_at" => $timestamp,
                "updated_at" => $timestamp,
            ],
            [
                "test_model_id" => $data0->id,
                "name" => "child 2",
                "created_at" => $timestamp,
                "updated_at" => $timestamp,
            ],
        ]);

        $data1 = TestModel::create([
            "name" => "test 2",
            "description" => "description 2",
            "created_at" => $timestamp,
            "updated_at" => $timestamp,
        ]);

        $data1->test_model_childs()->createMany([
            [
                "test_model_id" => $data1->id,
                "name" => "child 2",
                "created_at" => $timestamp,
                "updated_at" => $timestamp,
            ],
        ]);

        $data2 = TestModel::create([
            "name" => "test 3",
            "description" => "description 3",
            "created_at" => $timestamp,
            "updated_at" => $timestamp,
        ]);

        $data2->test_model_childs()->createMany([
            [
                "test_model_id" => $data2->id,
                "name" => "child 3",
                "created_at" => $timestamp,
                "updated_at" => $timestamp,
            ],
        ]);
    }
}
