<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marcas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->foreignId('proveedor_id')
                ->constrained('proveedores')
                ->onUpdate('cascade')
                ->onDelete('restrict'); // no se puede borrar un proveedor con marcas asociadas
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['nombre', 'proveedor_id']); // evita marcas duplicadas para un mismo proveedor
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marcas');
    }
};