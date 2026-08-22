<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedido_reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
           
            $table->unsignedInteger('cantidad');
            // activa: descontada solo de cantidad_reservada (reserva virtual)
            // confirmada: ya se desconto tambien de cantidad real (entrega hecha)
            // liberada: el admin la libero manualmente, no afecta nada
            $table->enum('estado', ['activa', 'confirmada', 'liberada'])->default('activa');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_reservas');
    }
};
