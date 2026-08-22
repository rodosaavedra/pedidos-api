<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_kardex', function (Blueprint $table) {

            $table->unsignedBigInteger('kardex_cab_id')
                ->after('id');

            $table->unsignedBigInteger('producto_id')
                ->after('kardex_cab_id');

            $table->unsignedBigInteger('inventario_id')
                ->after('producto_id');

            $table->unsignedInteger('cantidad')
                ->after('inventario_id');

            $table->unsignedInteger('cantidad_anterior')
                ->default(0)
                ->after('cantidad');

            $table->unsignedInteger('cantidad_nueva')
                ->default(0)
                ->after('cantidad_anterior');

            $table->unsignedInteger('cantidad_reservada_anterior')
                ->default(0)
                ->after('cantidad_nueva');

            $table->unsignedInteger('cantidad_reservada_nueva')
                ->default(0)
                ->after('cantidad_reservada_anterior');

            $table->unsignedInteger('saldo_disponible_anterior')
                ->default(0)
                ->after('cantidad_reservada_nueva');

            $table->unsignedInteger('saldo_disponible_nuevo')
                ->default(0)
                ->after('saldo_disponible_anterior');

            $table->decimal('precio_unitario', 12, 2)
                ->nullable()
                ->after('saldo_disponible_nuevo');

            $table->string('observacion', 255)
                ->nullable()
                ->after('precio_unitario');

            $table->index(
                'kardex_cab_id',
                'idx_detalle_kardex_cab'
            );

            $table->index(
                'producto_id',
                'idx_detalle_kardex_producto'
            );

            $table->index(
                'inventario_id',
                'idx_detalle_kardex_inventario'
            );
        });
    }

    public function down(): void
    {
        Schema::table('detalle_kardex', function (Blueprint $table) {

            $table->dropIndex(
                'idx_detalle_kardex_cab'
            );

            $table->dropIndex(
                'idx_detalle_kardex_producto'
            );

            $table->dropIndex(
                'idx_detalle_kardex_inventario'
            );

            $table->dropColumn([
                'kardex_cab_id',
                'producto_id',
                'inventario_id',
                'cantidad',
                'cantidad_anterior',
                'cantidad_nueva',
                'cantidad_reservada_anterior',
                'cantidad_reservada_nueva',
                'saldo_disponible_anterior',
                'saldo_disponible_nuevo',
                'precio_unitario',
                'observacion',
            ]);
        });
    }
};