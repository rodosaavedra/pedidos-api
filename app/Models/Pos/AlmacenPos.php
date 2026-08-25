<?php

namespace App\Models\Pos;

use Illuminate\Database\Eloquent\Model;

class AlmacenPos extends Model
{
    protected $connection = 'Pos';

    protected $table = 'almacenes';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}