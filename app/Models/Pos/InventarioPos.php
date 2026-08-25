<?php

namespace App\Models\Pos;

use Illuminate\Database\Eloquent\Model;

class InventarioPos extends Model
{
    protected $connection = 'Pos';

    protected $table = 'inventario';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}