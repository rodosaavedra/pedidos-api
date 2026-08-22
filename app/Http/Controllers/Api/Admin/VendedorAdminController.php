<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendedor;
use Illuminate\Http\Request;

class VendedorAdminController extends Controller
{
    /**
     * Listar vendedores activos.
     *
     * GET /api/admin/vendedores
     */
    public function index(Request $request)
    {
        $query = Vendedor::query();

        // Búsqueda por nombre o teléfono
        if ($request->filled('q')) {
            $q = trim($request->q);

            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('nombre', 'ILIKE', "%{$q}%")
                    ->orWhere('telefono', 'ILIKE', "%{$q}%");
            });
        }

        // Por defecto solamente vendedores activos
        if ($request->has('activo')) {
            $query->where('activo', filter_var(
                $request->activo,
                FILTER_VALIDATE_BOOLEAN
            ));
        } else {
            $query->where('activo', true);
        }

        $vendedores = $query
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vendedores,
        ]);
    }

    /**
     * Mostrar un vendedor.
     *
     * GET /api/admin/vendedores/{id}
     */
    public function show($id)
    {
        $vendedor = Vendedor::find($id);

        if (!$vendedor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendedor no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $vendedor,
        ]);
    }

    /**
     * Crear vendedor.
     *
     * POST /api/admin/vendedores
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
            ],
            'telefono' => [
                'nullable',
                'string',
                'max:50',
            ],
            'activo' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $validated['activo'] = $validated['activo'] ?? true;

        $vendedor = Vendedor::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vendedor creado correctamente.',
            'data' => $vendedor,
        ], 201);
    }

    /**
     * Actualizar vendedor.
     *
     * PUT/PATCH /api/admin/vendedores/{id}
     */
    public function update(Request $request, $id)
    {
        $vendedor = Vendedor::find($id);

        if (!$vendedor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendedor no encontrado.',
            ], 404);
        }

        $validated = $request->validate([
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'telefono' => [
                'nullable',
                'string',
                'max:50',
            ],
            'activo' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $vendedor->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vendedor actualizado correctamente.',
            'data' => $vendedor->fresh(),
        ]);
    }

    /**
     * Desactivar vendedor.
     *
     * DELETE /api/admin/vendedores/{id}
     *
     * No elimina físicamente el registro.
     */
    public function destroy($id)
    {
        $vendedor = Vendedor::find($id);

        if (!$vendedor) {
            return response()->json([
                'success' => false,
                'message' => 'Vendedor no encontrado.',
            ], 404);
        }

        $vendedor->update([
            'activo' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vendedor desactivado correctamente.',
            'data' => $vendedor->fresh(),
        ]);
    }
}