<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'venta';

    protected $fillable = [
        'pedido_id', 'almacen_id', 'vendedor_id',
        'nombre_cliente', 'celular_whatsapp', 'forma_pago', 'total', 'fecha_venta',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'fecha_venta' => 'datetime',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Vendedor::class, 'vendedor_id');
    }

    public function detalle(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }
}
