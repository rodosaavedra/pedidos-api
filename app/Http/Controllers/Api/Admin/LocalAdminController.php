<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Local;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocalAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Local::with('almacenes');

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);

            $query->where(function ($q) use ($buscar) {
                $q->where(
                    'nombre',
                    'LIKE',
                    "%{$buscar}%"
                )
                ->orWhere(
                    'direccion',
                    'LIKE',
                    "%{$buscar}%"
                );
            });
        }

        if ($request->filled('activo')) {
            $query->where(
                'activo',
                filter_var(
                    $request->activo,
                    FILTER_VALIDATE_BOOLEAN
                )
            );
        }

        $locales = $query
            ->orderBy('nombre')
            ->paginate(
                $request->integer('per_page', 20)
            );

        return response()->json([
            'success' => true,
            'data' => $locales,
        ]);
    }


    public function show(int $id): JsonResponse
    {
        $local = Local::with([
            'almacenes',
        ])->find($id);

        if (!$local) {
            return response()->json([
                'success' => false,
                'message' => 'Local no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $local,
        ]);
    }


    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
            ],

            'direccion' => [
                'nullable',
                'string',
                'max:255',
            ],

            'activo' => [
                'nullable',
                'boolean',
            ],
        ]);

        $local = Local::create([
            'nombre' => $datos['nombre'],
            'direccion' => $datos['direccion'] ?? null,
            'activo' => $datos['activo'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Local creado correctamente.',
            'data' => $local,
        ], 201);
    }


    public function update(
        Request $request,
        int $id
    ): JsonResponse {

        $local = Local::find($id);

        if (!$local) {
            return response()->json([
                'success' => false,
                'message' => 'Local no encontrado.',
            ], 404);
        }

        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
            ],

            'direccion' => [
                'nullable',
                'string',
                'max:255',
            ],

            'activo' => [
                'required',
                'boolean',
            ],
        ]);

        $local->update($datos);

        return response()->json([
            'success' => true,
            'message' => 'Local actualizado correctamente.',
            'data' => $local,
        ]);
    }


    public function cambiarEstado(int $id): JsonResponse
    {
        $local = Local::find($id);

        if (!$local) {
            return response()->json([
                'success' => false,
                'message' => 'Local no encontrado.',
            ], 404);
        }

        $local->activo = !$local->activo;
        $local->save();

        return response()->json([
            'success' => true,
            'message' => $local->activo
                ? 'Local activado.'
                : 'Local desactivado.',
            'data' => $local,
        ]);
    }
}