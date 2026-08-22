<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleKardex extends Model
{
    use HasFactory;

    protected $table = 'detalle_kardex';

    protected $fillable = [
        'id_kardex_cab',
        'id_producto',
        'id_inventario',

        'cantidad',

        'cantidad_anterior',
        'cantidad_nueva',

        'cantidad_reservada_anterior',
        'cantidad_reservada_nueva',

        'saldo_disponible_anterior',
        'saldo_disponible_nuevo',

        'precio_unitario',

        'observacion',
    ];

    protected $casts = [

        'cantidad' =>
            'decimal:2',

        'cantidad_anterior' =>
            'decimal:2',

        'cantidad_nueva' =>
            'decimal:2',

        'cantidad_reservada_anterior' =>
            'decimal:2',

        'cantidad_reservada_nueva' =>
            'decimal:2',

        'saldo_disponible_anterior' =>
            'decimal:2',

        'saldo_disponible_nuevo' =>
            'decimal:2',

        'precio_unitario' =>
            'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | CABECERA
    |--------------------------------------------------------------------------
    */
    public function kardexCab(): BelongsTo
    {
        return $this->belongsTo(
            KardexCab::class,
            'id_kardex_cab'
        );
    }
    /*public function cabecera(): BelongsTo
    {
        return $this->belongsTo(
            KardexCab::class,
            'id_kardex_cab'
        );
    }*/

    /*
    |--------------------------------------------------------------------------
    | PRODUCTO
    |--------------------------------------------------------------------------
    */

    public function producto(): BelongsTo
    {
        return $this->belongsTo(
            Producto::class,
            'id_producto'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | INVENTARIO
    |--------------------------------------------------------------------------
    */

    public function inventario(): BelongsTo
    {
        return $this->belongsTo(
            Inventario::class,
            'id_inventario'
        );
    }
}