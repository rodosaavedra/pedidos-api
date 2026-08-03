<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();

            // Datos del cliente guardados directamente en el pedido
            // (así queda un registro fijo aunque el cliente cambie de dirección/celular después)
            $table->string('nombre_cliente', 150);
            $table->string('celular_whatsapp', 20);
            $table->string('direccion_entrega', 255);

            $table->enum('estado', ['pendiente', 'confirmado', 'en_proceso', 'entregado', 'cancelado'])
                ->default('pendiente');

            $table->decimal('total', 10, 2)->default(0);
            $table->text('observaciones')->nullable();

            $table->timestamp('fecha_pedido')->useCurrent();
            $table->timestamps();

            $table->index('celular_whatsapp'); // para buscar pedidos anteriores del mismo cliente
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};