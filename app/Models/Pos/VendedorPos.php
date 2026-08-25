<?php

namespace App\Models\Pos;

use Illuminate\Database\Eloquent\Model;

class VendedorPos extends Model
{
    protected $connection = 'Pos';

    protected $table = 'usuarios';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $guarded = [];
}