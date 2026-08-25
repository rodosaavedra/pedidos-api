<?php

namespace App\Models\Vargas;

use Illuminate\Database\Eloquent\Model;

class DlleCotiza extends Model
{
    protected $connection = 'vargas';

    protected $table = 'dllecotiza';

    protected $fillable = [
        'id_cotiza',
        'id_dlle',
        'id_producto',
        'descripcion',
        'cantidad',
        'saldo_inv',
        'pre_uni',
        'descuento',
        'importe',
        'total',
        'desc_total',
        'estado',
    ];

    public $timestamps = false;

    protected $casts = [
        'cantidad' => 'float',
        'saldo_inv' => 'float',
        'pre_uni' => 'float',
        'descuento' => 'float',
        'importe' => 'float',
        'total' => 'float',
        'desc_total' => 'float',
    ];
}