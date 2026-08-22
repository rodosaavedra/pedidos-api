<?php

namespace App\Models\Vargas;

use Illuminate\Database\Eloquent\Model;

class VendedorVargas extends Model
{
    protected $connection = 'vargas';

    protected $table = 'usuarios';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}