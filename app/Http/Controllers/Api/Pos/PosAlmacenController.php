<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\AlmacenPos;
use Illuminate\Http\Request;
use Throwable;

class PosAlmacenController extends Controller
{
    public function index(Request $request)
    {
        try {

            $query = AlmacenPos::query()
                ->where('estado', 1);

            if ($request->filled('id_local')) {

                $query->where(
                    'id_local',
                    $request->integer('id_local')
                );
            }

            if ($request->filled('q')) {

                $q = trim($request->q);

                $query->where(
                    'nombre',
                    'like',
                    "%{$q}%"
                );
            }

            $almacenes = $query
                ->orderBy('nombre')
                ->get();

            return response()->json([
                'ok' => true,
                'data' => $almacenes,
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'ok' => false,
                'mensaje' => 'Error al consultar almacenes de DB_Pos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function show(int $id)
    {
        try {

            $almacen = AlmacenPos::find($id);

            if (!$almacen) {

                return response()->json([
                    'ok' => false,
                    'mensaje' => 'Almacén no encontrado',
                ], 404);
            }

            return response()->json([
                'ok' => true,
                'data' => $almacen,
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'ok' => false,
                'mensaje' => 'Error al consultar almacén',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}