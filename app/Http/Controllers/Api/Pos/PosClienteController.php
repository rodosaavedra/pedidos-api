<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\ClientePos;
use Illuminate\Http\Request;
use Throwable;

class PosClienteController extends Controller
{
    public function index(Request $request)
    {
        try {

            $query = ClientePos::query()
                ->where('estado', 1);

            if ($request->filled('q')) {

                $q = trim($request->q);

                $query->where(function ($query) use ($q) {

                    $query->where('nombre', 'like', "%{$q}%")
                        ->orWhere('documento', 'like', "%{$q}%")
                        ->orWhere('telefono', 'like', "%{$q}%");

                });
            }

            $clientes = $query
                ->orderBy('nombre')
                ->paginate(
                    $request->integer('per_page', 50)
                );

            return response()->json([
                'ok' => true,
                'data' => $clientes,
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'ok' => false,
                'mensaje' => 'Error al consultar clientes de DB_Pos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function show(int $id)
    {
        try {

            $cliente = ClientePos::find($id);

            if (!$cliente) {

                return response()->json([
                    'ok' => false,
                    'mensaje' => 'Cliente no encontrado',
                ], 404);
            }

            return response()->json([
                'ok' => true,
                'data' => $cliente,
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'ok' => false,
                'mensaje' => 'Error al consultar cliente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}