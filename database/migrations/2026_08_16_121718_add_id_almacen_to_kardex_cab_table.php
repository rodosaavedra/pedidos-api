<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kardex_cab', function (Blueprint $table) {
            if (! Schema::hasColumn('kardex_cab', 'id_almacen')) {
                $table->foreignId('id_almacen')
                    ->after('numero')
                    ->constrained('almacenes')
                    ->onDelete('restrict');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kardex_cab', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_almacen');
        });
    }
};