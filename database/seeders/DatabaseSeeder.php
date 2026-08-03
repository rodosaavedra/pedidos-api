<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategoriaSeeder::class,
            ProveedorSeeder::class,
            MarcaSeeder::class,   // depende de proveedores
            ProductoSeeder::class, // depende de categoria y marcas
        ]);
    }
}
