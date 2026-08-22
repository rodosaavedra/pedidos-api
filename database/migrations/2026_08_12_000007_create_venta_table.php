<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta', function (Blueprint $table) {
            $table->id();
            // Nullable por si en el futuro se registra una venta de mostrador
            // sin pedido previo - pero el flujo principal siempre la llena.
            $table->foreignId('pedido_id')->nullable()->constrained('pedidos')->nullOnDelete();
            $table->foreignId('almacen_id')->constrained('almacenes');
            $table->foreignId('vendedor_id')->constrained('vendedores');
            $table->string('nombre_cliente', 150);
            $table->string('celular_whatsapp', 20)->nullable();
            $table->string('forma_pago', 30);
            $table->decimal('total', 10, 2);
            $table->timestamp('fecha_venta')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta');
    }
};
