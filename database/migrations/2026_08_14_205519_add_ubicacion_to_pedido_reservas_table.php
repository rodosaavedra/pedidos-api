<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedido_reservas', function (Blueprint $table) {

            $table->unsignedBigInteger('almacen_id')
                ->nullable()
                ->after('inventario_id');

            $table->unsignedBigInteger('local_id')
                ->nullable()
                ->after('almacen_id');

            $table->unsignedBigInteger('vendedor_id')
                ->nullable()
                ->after('local_id');

            $table->foreign('almacen_id')
                ->references('id')
                ->on('almacenes')
                ->nullOnDelete();

            $table->foreign('local_id')
                ->references('id')
                ->on('locales')
                ->nullOnDelete();

            $table->foreign('vendedor_id')
                ->references('id')
                ->on('vendedores')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pedido_reservas', function (Blueprint $table) {

            $table->dropForeign([
                'almacen_id'
            ]);

            $table->dropForeign([
                'local_id'
            ]);

            $table->dropForeign([
                'vendedor_id'
            ]);

            $table->dropColumn([
                'almacen_id',
                'local_id',
                'vendedor_id',
            ]);
        });
    }
};