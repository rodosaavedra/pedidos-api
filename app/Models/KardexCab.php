<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KardexCab extends Model
{
    use HasFactory;

    protected $table = 'kardex_cab';

    protected $fillable = [
        'numero',
        'id_almacen',
        'id_local',
        'id_vendedor',
        'fecha',
        'tipo_transaccion',
        'id_usuario',
        'observacion',
        'activo',
        'fecha_anulacion',
        'id_usuario_anulacion',
        'motivo_anulacion',
    ];

    protected $casts = [
        'numero' => 'integer',
        'fecha' => 'datetime',
        'activo' => 'boolean',
        'fecha_anulacion' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | ALMACÉN
    |--------------------------------------------------------------------------
    */

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(
            Almacen::class,
            'id_almacen'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | LOCAL
    |--------------------------------------------------------------------------
    */

    public function local(): BelongsTo
    {
        return $this->belongsTo(
            Local::class,
            'id_local'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | VENDEDOR
    |--------------------------------------------------------------------------
    */

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(
            Vendedor::class,
            'id_vendedor'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | USUARIO QUE CREÓ EL MOVIMIENTO
    |--------------------------------------------------------------------------
    */

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'id_usuario'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | USUARIO QUE ANULÓ
    |--------------------------------------------------------------------------
    */

    public function usuarioAnulacion(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'id_usuario_anulacion'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DETALLES
    |--------------------------------------------------------------------------
    */

    public function detalles(): HasMany
    {
        return $this->hasMany(
            DetalleKardex::class,
            'id_kardex_cab'
        );
    }
}