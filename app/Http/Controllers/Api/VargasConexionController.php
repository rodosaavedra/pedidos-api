<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class VargasConexionController extends Controller
{
    public function probar()
    {
        try {
            DB::connection('vargas')->getPdo();

            return response()->json([
                'ok' => true,
                'mensaje' => 'Conexión con db_vargas correcta',
                'base_datos' => DB::connection('vargas')
                    ->getDatabaseName(),
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'ok' => false,
                'mensaje' => 'No se pudo conectar con db_vargas',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}