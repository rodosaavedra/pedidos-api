<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Almacen extends Model
{
    use HasFactory;

    protected $table = 'almacenes';

    protected $fillable = ['local_id', 'nombre', 'prioridad', 'activo'];

    protected $casts = [
        'prioridad' => 'integer',
        'activo' => 'boolean',
    ];

    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class, 'local_id');
    }

    public function inventarios(): HasMany
    {
        return $this->hasMany(Inventario::class, 'almacen_id');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'almacen_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'almacen_id');
    }

    /**
     * Almacenes activos ordenados por prioridad (menor primero = se
     * intenta antes al buscar donde atender un pedido).
     */
    public function scopeOrdenPorPrioridad($query)
    {
        return $query->where('activo', true)->orderBy('prioridad');
    }

    public function kardex(): HasMany
    {
        return $this->hasMany(
            KardexCab::class,
            'id_almacen'
        );
    }
}
