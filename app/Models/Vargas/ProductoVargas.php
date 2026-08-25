<?php

namespace App\Models\Vargas;

use Illuminate\Database\Eloquent\Model;

class ProductoVargas extends Model
{
    protected $connection = 'vargas';

    protected $table = 'productos';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}