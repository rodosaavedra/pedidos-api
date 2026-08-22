<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Almacen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlmacenAdminController extends Controller
{
    /**
     * GET /api/admin/almacenes
     *
     * Lista los almacenes disponibles para administración.
     */
    public function index(Request $request): JsonResponse
    {
        $almacenes = Almacen::query()
            ->when(
                $request->filled('q'),
                function ($query) use ($request) {
                    $texto = $request->input('q');

                    $query->where(function ($q) use ($texto) {
                        $q->where(
                            'nombre',
                            'like',
                            "%{$texto}%"
                        );

                        // Si tu tabla tiene código:
                        $q->orWhere(
                            'codigo',
                            'like',
                            "%{$texto}%"
                        );
                    });
                }
            )
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'data' => $almacenes,
        ]);
    }

    /**
     * GET /api/admin/almacenes/{almacen}
     */
    public function show(Almacen $almacen): JsonResponse
    {
        return response()->json([
            'data' => $almacen,
        ]);
    }

    /**
     * POST /api/admin/almacenes
     */
    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:150',
            ],

            'codigo' => [
                'nullable',
                'string',
                'max:50',
            ],

            'descripcion' => [
                'nullable',
                'string',
            ],

            'activo' => [
                'nullable',
                'boolean',
            ],
        ]);

        $almacen = Almacen::create([
            'nombre' =>
                $datos['nombre'],

            'codigo' =>
                $datos['codigo'] ?? null,

            'descripcion' =>
                $datos['descripcion'] ?? null,

            'activo' =>
                $datos['activo'] ?? true,
        ]);

        return response()->json([
            'message' =>
                'Almacén creado correctamente.',

            'data' =>
                $almacen,
        ], 201);
    }

    /**
     * PUT/PATCH /api/admin/almacenes/{almacen}
     */
    public function update(
        Request $request,
        Almacen $almacen
    ): JsonResponse {

        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:150',
            ],

            'codigo' => [
                'nullable',
                'string',
                'max:50',
            ],

            'descripcion' => [
                'nullable',
                'string',
            ],

            'activo' => [
                'nullable',
                'boolean',
            ],
        ]);

        $almacen->update($datos);

        return response()->json([
            'message' =>
                'Almacén actualizado correctamente.',

            'data' =>
                $almacen->fresh(),
        ]);
    }

    /**
     * DELETE /api/admin/almacenes/{almacen}
     */
    public function destroy(
        Almacen $almacen
    ): JsonResponse {

        /*
         * No recomendamos eliminar físicamente un almacén
         * que ya tenga movimientos o inventario.
         *
         * Por ahora se desactiva.
         */

        $almacen->update([
            'activo' => false,
        ]);

        return response()->json([
            'message' =>
                'Almacén desactivado correctamente.',
        ]);
    }
}