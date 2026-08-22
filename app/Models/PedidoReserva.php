<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoReserva extends Model
{
    use HasFactory;

    protected $table = 'pedido_reservas';

    protected $fillable = [
        'pedido_id',
        'producto_id',
        'almacen_id',
        'local_id',
        'vendedor_id',
        'cantidad',
        'fecha_reserva',
        'estado',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'fecha_reserva' => 'datetime',
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class, 'local_id');
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Vendedor::class, 'vendedor_id');
    }
}