<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestModel extends Model
{
    protected $table = 'test_models';
    // Add any necessary configurations for the model here

    public function test_model_childs()
    {
        return $this->hasMany('App\Models\TestModelChild', "test_model_id");
    }
}
