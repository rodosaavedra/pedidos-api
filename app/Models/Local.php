<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Local extends Model
{
    use HasFactory;

    protected $table = 'locales';

    protected $fillable = ['nombre', 'direccion', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function almacenes(): HasMany
    {
        return $this->hasMany(Almacen::class, 'local_id');
    }

    public function inventarios(): HasMany
    {
        return $this->hasMany(Inventario::class, 'local_id');
    }
}
