<?php

namespace App\Models\Vargas;

use Illuminate\Database\Eloquent\Model;

class ClienteVargas extends Model
{
    protected $connection = 'vargas';

    protected $table = 'clientes';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}