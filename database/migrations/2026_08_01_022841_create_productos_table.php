<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50)->unique();
            $table->string('descripcion', 255);

            $table->foreignId('categoria_id')
                ->constrained('categoria')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreignId('marca_id')
                ->nullable()
                ->constrained('marcas')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->decimal('precio', 10, 2);
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('categoria_id');
            $table->index('marca_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};