<?php

namespace App\Models\Vargas;

use Illuminate\Database\Eloquent\Model;

class AlmacenVargas extends Model
{
    protected $connection = 'vargas';

    protected $table = 'almacenes';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}