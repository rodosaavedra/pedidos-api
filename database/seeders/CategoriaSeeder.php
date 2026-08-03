<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            ['nombre' => 'Frenos', 'descripcion' => 'Sistemas y componentes de frenado', 'imagen' => 'https://placehold.co/160x100/0B5FBF/FFFFFF?text=Frenos'],
            ['nombre' => 'Embragues', 'descripcion' => 'Discos, platos y collarines de embrague', 'imagen' => 'https://placehold.co/160x100/0B5FBF/FFFFFF?text=Embragues'],
            ['nombre' => 'Filtros', 'descripcion' => 'Filtros de aceite, aire y combustible', 'imagen' => 'https://placehold.co/160x100/0B5FBF/FFFFFF?text=Filtros'],
            ['nombre' => 'Lubricantes', 'descripcion' => 'Aceites y grasas', 'imagen' => 'https://placehold.co/160x100/0B5FBF/FFFFFF?text=Lubricantes'],
            ['nombre' => 'Suspensión', 'descripcion' => 'Amortiguadores y componentes de suspensión', 'imagen' => 'https://placehold.co/160x100/0B5FBF/FFFFFF?text=Suspension'],
        ];

        foreach ($categorias as $categoria) {
            Categoria::create($categoria + ['activo' => true]);
        }
    }
}