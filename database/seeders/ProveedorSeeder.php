<?php

namespace Database\Seeders;

use App\Models\Proveedor;
use Illuminate\Database\Seeder;

class ProveedorSeeder extends Seeder
{
    public function run(): void
    {
        $proveedores = [
            [
                'nombre' => 'Distribuidora Andina S.R.L.',
                'contacto' => 'Juan Pérez',
                'telefono' => '70000001',
                'email' => 'ventas@distandina.com',
                'direccion' => 'Av. Ejemplo 123',
            ],
            [
                'nombre' => 'Importadora Central',
                'contacto' => 'María Rojas',
                'telefono' => '70000002',
                'email' => 'contacto@importcentral.com',
                'direccion' => 'Calle Comercio 456',
            ],
            [
                'nombre' => 'Repuestos del Sur',
                'contacto' => 'Carlos Mamani',
                'telefono' => '70000003',
                'email' => 'info@repuestosdelsur.com',
                'direccion' => 'Zona Industrial, Lote 8',
            ],
        ];

        foreach ($proveedores as $proveedor) {
            Proveedor::create($proveedor + ['activo' => true]);
        }
    }
}
