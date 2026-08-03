<?php

namespace Database\Seeders;

use App\Models\Marca;
use App\Models\Proveedor;
use Illuminate\Database\Seeder;

class MarcaSeeder extends Seeder
{
    public function run(): void
    {
        $andina = Proveedor::where('nombre', 'Distribuidora Andina S.R.L.')->first();
        $central = Proveedor::where('nombre', 'Importadora Central')->first();
        $sur = Proveedor::where('nombre', 'Repuestos del Sur')->first();

        $marcas = [
            ['nombre' => 'Bosch', 'proveedor_id' => $andina->id, 'imagen' => 'https://placehold.co/160x100/29B6F6/063A57?text=Bosch'],
            ['nombre' => 'Fras-le', 'proveedor_id' => $andina->id, 'imagen' => 'https://placehold.co/160x100/29B6F6/063A57?text=Fras-le'],
            ['nombre' => 'Sachs', 'proveedor_id' => $central->id, 'imagen' => 'https://placehold.co/160x100/29B6F6/063A57?text=Sachs'],
            ['nombre' => 'Mann Filter', 'proveedor_id' => $central->id, 'imagen' => 'https://placehold.co/160x100/29B6F6/063A57?text=Mann+Filter'],
            ['nombre' => 'Valvoline', 'proveedor_id' => $sur->id, 'imagen' => 'https://placehold.co/160x100/29B6F6/063A57?text=Valvoline'],
            ['nombre' => 'Monroe', 'proveedor_id' => $sur->id, 'imagen' => 'https://placehold.co/160x100/29B6F6/063A57?text=Monroe'],
        ];

        foreach ($marcas as $marca) {
            Marca::create($marca + ['activo' => true]);
        }
    }
}