<?php

namespace App\Models\Vargas;

use Illuminate\Database\Eloquent\Model;

class InventarioVargas extends Model
{
    protected $connection = 'vargas';

    protected $table = 'inventario';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}