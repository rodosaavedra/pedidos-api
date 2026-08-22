<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('almacen_id')->constrained('almacenes')->cascadeOnDelete();
            $table->foreignId('local_id')->constrained('locales')->cascadeOnDelete();
            $table->foreignId('vendedor_id')->constrained('vendedores')->cascadeOnDelete();
            $table->unsignedInteger('cantidad')->default(0);
            $table->unsignedInteger('cantidad_reservada')->default(0);
            $table->timestamps();

            // Un producto solo tiene UNA fila de stock por combinacion
            // almacen+vendedor; evita filas duplicadas que descuadren el total.
            $table->unique(['producto_id', 'almacen_id', 'vendedor_id'], 'inventarios_producto_almacen_vendedor_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventarios');
    }
};
