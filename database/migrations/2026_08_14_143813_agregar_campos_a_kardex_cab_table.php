<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kardex_cab', function (Blueprint $table) {

            $table->unsignedBigInteger('numero')
                ->nullable()
                ->after('id');

            $table->enum('tipo_movimiento', [
                'INGRESO',
                'EGRESO'
            ])
                ->after('numero');

            $table->unsignedBigInteger('almacen_id')
                ->after('tipo_movimiento');

            $table->unsignedBigInteger('local_id')
                ->nullable()
                ->after('almacen_id');

            $table->unsignedBigInteger('vendedor_id')
                ->nullable()
                ->after('local_id');

            $table->unsignedBigInteger('usuario_id')
                ->nullable()
                ->after('vendedor_id');

            $table->dateTime('fecha_movimiento')
                ->after('usuario_id');

            $table->string('observacion', 255)
                ->nullable()
                ->after('fecha_movimiento');

            $table->enum('estado', [
                'ACTIVO',
                'ANULADO'
            ])
                ->default('ACTIVO')
                ->after('observacion');

            $table->dateTime('fecha_anulacion')
                ->nullable()
                ->after('estado');

            $table->unsignedBigInteger('usuario_anulacion')
                ->nullable()
                ->after('fecha_anulacion');

            $table->string('motivo_anulacion', 255)
                ->nullable()
                ->after('usuario_anulacion');

            $table->index(
                'almacen_id',
                'idx_kardex_cab_almacen'
            );

            $table->index(
                'fecha_movimiento',
                'idx_kardex_cab_fecha'
            );

            $table->index(
                'tipo_movimiento',
                'idx_kardex_cab_tipo'
            );

            $table->index(
                'usuario_id',
                'idx_kardex_cab_usuario'
            );

            $table->index(
                'estado',
                'idx_kardex_cab_estado'
            );
        });
    }

    public function down(): void
    {
        Schema::table('kardex_cab', function (Blueprint $table) {

            $table->dropIndex(
                'idx_kardex_cab_almacen'
            );

            $table->dropIndex(
                'idx_kardex_cab_fecha'
            );

            $table->dropIndex(
                'idx_kardex_cab_tipo'
            );

            $table->dropIndex(
                'idx_kardex_cab_usuario'
            );

            $table->dropIndex(
                'idx_kardex_cab_estado'
            );

            $table->dropColumn([
                'numero',
                'tipo_movimiento',
                'almacen_id',
                'local_id',
                'vendedor_id',
                'usuario_id',
                'fecha_movimiento',
                'observacion',
                'estado',
                'fecha_anulacion',
                'usuario_anulacion',
                'motivo_anulacion',
            ]);
        });
    }
};