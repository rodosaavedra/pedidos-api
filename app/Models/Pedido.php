<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre_cliente',
        'celular_whatsapp',
        'direccion_entrega',
        'estado',
        'total',
        'observaciones',
        'fecha_pedido',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'fecha_pedido' => 'datetime',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(DetallePedido::class);
    }
}