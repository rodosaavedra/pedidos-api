<?php

namespace App\Http\Controllers\Api\Vargas;

use App\Http\Controllers\Controller;
use App\Models\Vargas\ProductoVargas;
use Illuminate\Http\Request;
use Throwable;

class VargasProductoController extends Controller
{
    public function index(Request $request)
    {
        try {

            $query = ProductoVargas::query()
                ->where('estado', 1);

            if ($request->filled('q')) {

                $q = trim($request->q);
                

                $query->where(function ($query) use ($q) {

                    $query->where('codigo', 'like', "%{$q}%")
                        ->orWhere('descripcion', 'like', "%{$q}%");

                });
            }

            $productos = $query
                ->orderBy('descripcion')
                ->paginate(
                    $request->integer('per_page', 50)
                );

            return response()->json([
                'ok' => true,
                'data' => $productos,
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'ok' => false,
                'mensaje' => 'Error al consultar productos de DB_VARGAS',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function show(int $id)
    {
        try {

            $producto = ProductoVargas::find($id);

            if (!$producto) {

                return response()->json([
                    'ok' => false,
                    'mensaje' => 'Producto no encontrado',
                ], 404);
            }

            return response()->json([
                'ok' => true,
                'data' => $producto,
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'ok' => false,
                'mensaje' => 'Error al consultar producto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}