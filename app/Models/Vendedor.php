<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendedor extends Model
{
    use HasFactory;

    protected $table = 'vendedores';

    protected $fillable = ['nombre', 'telefono', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function inventarios(): HasMany
    {
        return $this->hasMany(Inventario::class, 'vendedor_id');
    }

    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'vendedor_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'vendedor_id');
    }
}
