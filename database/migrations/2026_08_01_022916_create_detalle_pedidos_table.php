<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_pedidos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pedido_id')
                ->constrained('pedidos')
                ->onDelete('cascade'); // si se borra el pedido, se borra su detalle

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->onDelete('restrict'); // no se puede borrar un producto con pedidos asociados

            // Snapshot del producto al momento del pedido, por si luego cambia
            // código, descripción o precio en el catálogo
            $table->string('codigo_producto', 50);
            $table->string('descripcion_producto', 255);

            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 10, 2); // precio tomado del backend, nunca del frontend
            $table->decimal('subtotal', 10, 2);

            $table->timestamps();

            $table->index('pedido_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_pedidos');
    }
};