<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestModelChildsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("test_model_childs", function (Blueprint $table) {
            if (version_compare(app()->version(), "8.0.0") >= 0) {
                $table->id();
            } else {
                $table->increments("id");
            }
            $table->integer("test_model_id")->nullable();
            $table->string("name")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("test_model_childs");
    }
}
