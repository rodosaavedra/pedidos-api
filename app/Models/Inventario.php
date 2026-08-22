<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inventario extends Model
{
    use HasFactory;

    protected $table = 'inventarios';

    protected $fillable = [
        'producto_id', 'almacen_id', 'local_id', 'vendedor_id',
        'cantidad', 'cantidad_reservada',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'cantidad_reservada' => 'integer',
    ];

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

    public function reservas(): HasMany
    {
        return $this->hasMany(PedidoReserva::class, 'inventario_id');
    }
  
    /**
     * Lo que realmente se puede prometer a un nuevo pedido: el stock real
     * menos lo que ya esta reservado por otros pedidos sin confirmar.
     */
    protected function cantidadDisponible(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->cantidad - $this->cantidad_reservada
        );
    }

    /**
     * Reserva virtual: NO toca cantidad real, solo cantidad_reservada.
     */
    public function reservar(int $unidades): void
    {
        $this->increment('cantidad_reservada', $unidades);
    }

    /**
     * Se llama al confirmar/entregar el pedido: recien aqui se descuenta
     * el stock real, y se libera la reserva correspondiente.
     */
    public function confirmarReserva(int $unidades): void
    {
        $this->decrement('cantidad', $unidades);
        $this->decrement('cantidad_reservada', $unidades);
    }

    /**
     * El admin libera manualmente una reserva que nunca se confirmo
     * (pedido abandonado/cancelado). No toca el stock real.
     */
    public function liberarReserva(int $unidades): void
    {
        $this->decrement('cantidad_reservada', $unidades);
    }
}
