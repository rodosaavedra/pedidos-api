<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\ProductoPos;
use Illuminate\Http\Request;
use Throwable;

class PosProductoController extends Controller
{
    public function index(Request $request)
    {
        try {

            $query = ProductoPos::query()
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
                'mensaje' => 'Error al consultar productos de DB_Pos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function show(int $id)
    {
        try {

            $producto = ProductoPos::find($id);

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