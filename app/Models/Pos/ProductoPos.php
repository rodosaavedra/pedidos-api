<?php

namespace App\Models\Pos;

use Illuminate\Database\Eloquent\Model;

class ProductoPos extends Model
{
    protected $connection = 'Pos';

    protected $table = 'productos';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}