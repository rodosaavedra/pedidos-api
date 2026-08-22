<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            // Nullable: el pedido nace sin almacen/vendedor asignado hasta que
            // el sistema encuentra uno con stock suficiente (por prioridad).
            $table->foreignId('almacen_id')->nullable()->after('id')
                ->constrained('almacenes')->nullOnDelete();
            $table->foreignId('vendedor_id')->nullable()->after('almacen_id')
                ->constrained('vendedores')->nullOnDelete();

            // Se define recien al confirmar/entregar, no al crear el pedido.
            $table->string('forma_pago', 30)->nullable()->after('total');

            // Evita generar la venta duplicada si el admin presiona el boton
            // "Realizar venta" mas de una vez sobre el mismo pedido.
            $table->timestamp('vendido_at')->nullable()->after('forma_pago');
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('almacen_id');
            $table->dropConstrainedForeignId('vendedor_id');
            $table->dropColumn(['forma_pago', 'vendido_at']);
        });
    }
};
