<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $frenos = Categoria::where('nombre', 'Frenos')->first();
        $embragues = Categoria::where('nombre', 'Embragues')->first();
        $filtros = Categoria::where('nombre', 'Filtros')->first();
        $lubricantes = Categoria::where('nombre', 'Lubricantes')->first();
        $suspension = Categoria::where('nombre', 'Suspensión')->first();

        $bosch = Marca::where('nombre', 'Bosch')->first();
        $frasle = Marca::where('nombre', 'Fras-le')->first();
        $sachs = Marca::where('nombre', 'Sachs')->first();
        $mann = Marca::where('nombre', 'Mann Filter')->first();
        $valvoline = Marca::where('nombre', 'Valvoline')->first();
        $monroe = Marca::where('nombre', 'Monroe')->first();

        $productos = [
            ['codigo' => 'FRE-001', 'descripcion' => 'Pastillas de freno delanteras', 'categoria_id' => $frenos->id, 'marca_id' => $bosch->id, 'precio' => 180.00, 'stock' => 25],
            ['codigo' => 'FRE-002', 'descripcion' => 'Disco de freno ventilado', 'categoria_id' => $frenos->id, 'marca_id' => $frasle->id, 'precio' => 320.00, 'stock' => 12],
            ['codigo' => 'EMB-001', 'descripcion' => 'Kit de embrague completo', 'categoria_id' => $embragues->id, 'marca_id' => $sachs->id, 'precio' => 950.00, 'stock' => 8],
            ['codigo' => 'EMB-002', 'descripcion' => 'Collarín de embrague', 'categoria_id' => $embragues->id, 'marca_id' => $sachs->id, 'precio' => 210.00, 'stock' => 15],
            ['codigo' => 'FIL-001', 'descripcion' => 'Filtro de aceite', 'categoria_id' => $filtros->id, 'marca_id' => $mann->id, 'precio' => 45.00, 'stock' => 60],
            ['codigo' => 'FIL-002', 'descripcion' => 'Filtro de aire', 'categoria_id' => $filtros->id, 'marca_id' => $mann->id, 'precio' => 55.00, 'stock' => 40],
            ['codigo' => 'LUB-001', 'descripcion' => 'Aceite de motor 20W-50 x 1 galón', 'categoria_id' => $lubricantes->id, 'marca_id' => $valvoline->id, 'precio' => 130.00, 'stock' => 30],
            ['codigo' => 'LUB-002', 'descripcion' => 'Grasa multipropósito 1kg', 'categoria_id' => $lubricantes->id, 'marca_id' => $valvoline->id, 'precio' => 38.00, 'stock' => 20],
            ['codigo' => 'SUS-001', 'descripcion' => 'Amortiguador delantero', 'categoria_id' => $suspension->id, 'marca_id' => $monroe->id, 'precio' => 410.00, 'stock' => 10],
            ['codigo' => 'SUS-002', 'descripcion' => 'Amortiguador trasero', 'categoria_id' => $suspension->id, 'marca_id' => $monroe->id, 'precio' => 380.00, 'stock' => 10],
        ];

        foreach ($productos as $producto) {
            Producto::create($producto + ['activo' => true]);
        }
    }
}
