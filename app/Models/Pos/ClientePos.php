<?php

namespace App\Models\Pos;

use Illuminate\Database\Eloquent\Model;

class ClientePos extends Model
{
    protected $connection = 'Pos';

    protected $table = 'clientes';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}