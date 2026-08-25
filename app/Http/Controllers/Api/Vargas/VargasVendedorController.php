<?php

namespace App\Http\Controllers\Api\Vargas;

use App\Http\Controllers\Controller;
use App\Models\Vargas\VendedorVargas;
use Illuminate\Http\Request;
use Throwable;

class VargasVendedorController extends Controller
{
    public function index(Request $request)
    {
        try {

            $query = VendedorVargas::query();

            if ($request->filled('q')) {

                $q = trim($request->q);

                $query->where(
                    'nombre',
                    'like',
                    "%{$q}%"
                );
            }

            $vendedores = $query
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'ok' => true,
                'data' => $vendedores,
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'ok' => false,
                'mensaje' => 'Error al consultar vendedores de DB_VARGAS',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function show(int $id)
    {
        try {

            $vendedor = VendedorVargas::find($id);

            if (!$vendedor) {

                return response()->json([
                    'ok' => false,
                    'mensaje' => 'Vendedor no encontrado',
                ], 404);
            }

            return response()->json([
                'ok' => true,
                'data' => $vendedor,
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'ok' => false,
                'mensaje' => 'Error al consultar vendedor',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}