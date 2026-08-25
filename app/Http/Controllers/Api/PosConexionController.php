<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PosConexionController extends Controller
{
    public function probar()
    {
        try {
            DB::connection('Pos')->getPdo();

            return response()->json([
                'ok' => true,
                'mensaje' => 'Conexión con db_Pos correcta',
                'base_datos' => DB::connection('Pos')
                    ->getDatabaseName(),
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'ok' => false,
                'mensaje' => 'No se pudo conectar con db_Pos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}