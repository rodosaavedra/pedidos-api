<?php

namespace App\Models\Pos;

use Illuminate\Database\Eloquent\Model;

class Cotiza extends Model
{
    protected $connection = 'Pos';

    protected $table = 'cotiza';

    protected $fillable = [
        'codigo',
        'id_cliente',
        'id_usuario',
        'productos',
        'impuesto',
        'neto',
        'total',
        'metodo_pago',
        'fecha',
        'id_local',
        'id_almacen',
        'valido',
        'estado',
        'cargo',
        'desc_porc',
    ];

    public $timestamps = false;

    protected $casts = [
        'fecha' => 'date',
        'impuesto' => 'float',
        'neto' => 'float',
        'total' => 'float',
        'cargo' => 'float',
        'desc_porc' => 'float',
    ];
}