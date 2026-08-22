<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductoAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Producto::with([
            'categoria',
            'marca',
        ]);

        if ($request->filled('buscar')) {
            $buscar = trim($request->buscar);

            $query->where(function ($q) use ($buscar) {
                $q->where('codigo', 'LIKE', "%{$buscar}%")
                  ->orWhere(
                      'descripcion',
                      'LIKE',
                      "%{$buscar}%"
                  );
            });
        }

        if ($request->filled('categoria_id')) {
            $query->where(
                'categoria_id',
                $request->categoria_id
            );
        }

        if ($request->filled('marca_id')) {
            $query->where(
                'marca_id',
                $request->marca_id
            );
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

        $productos = $query
            ->orderBy('descripcion')
            ->paginate(
                $request->integer('per_page', 20)
            );

        return response()->json([
            'success' => true,
            'data' => $productos,
        ]);
    }


    public function buscar(Request $request): JsonResponse
    {
        $buscar = trim(
            $request->input('buscar', '')
        );

        if ($buscar === '') {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $productos = Producto::with([
            'categoria',
            'marca',
        ])
        ->where('activo', true)
        ->where(function ($query) use ($buscar) {
            $query
                ->where(
                    'codigo',
                    'LIKE',
                    "%{$buscar}%"
                )
                ->orWhere(
                    'descripcion',
                    'LIKE',
                    "%{$buscar}%"
                );
        })
        ->orderBy('descripcion')
        ->limit(30)
        ->get();

        return response()->json([
            'success' => true,
            'data' => $productos,
        ]);
    }


    public function show(int $id): JsonResponse
    {
        $producto = Producto::with([
            'categoria',
            'marca',
            'inventarios.almacen',
            'inventarios.local',
            'inventarios.vendedor',
        ])->find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $producto,
        ]);
    }


    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:100',
                'unique:productos,codigo',
            ],

            'descripcion' => [
                'required',
                'string',
                'max:255',
            ],

            'categoria_id' => [
                'nullable',
                'integer',
                'exists:categorias,id',
            ],

            'marca_id' => [
                'nullable',
                'integer',
                'exists:marcas,id',
            ],

            'precio' => [
                'required',
                'numeric',
                'gte:0',
            ],

            'stock' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'activo' => [
                'nullable',
                'boolean',
            ],
        ]);

        $producto = Producto::create([
            'codigo' => $datos['codigo'],
            'descripcion' => $datos['descripcion'],
            'categoria_id' => $datos['categoria_id'] ?? null,
            'marca_id' => $datos['marca_id'] ?? null,
            'precio' => $datos['precio'],
            'stock' => $datos['stock'] ?? 0,
            'activo' => $datos['activo'] ?? true,
        ]);

        $producto->load([
            'categoria',
            'marca',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Producto creado correctamente.',
            'data' => $producto,
        ], 201);
    }


    public function update(
        Request $request,
        int $id
    ): JsonResponse {

        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado.',
            ], 404);
        }

        $datos = $request->validate([
            'codigo' => [
                'required',
                'string',
                'max:100',
                'unique:productos,codigo,' . $producto->id,
            ],

            'descripcion' => [
                'required',
                'string',
                'max:255',
            ],

            'categoria_id' => [
                'nullable',
                'integer',
                'exists:categorias,id',
            ],

            'marca_id' => [
                'nullable',
                'integer',
                'exists:marcas,id',
            ],

            'precio' => [
                'required',
                'numeric',
                'gte:0',
            ],

            'activo' => [
                'required',
                'boolean',
            ],
        ]);

        $producto->update($datos);

        $producto->load([
            'categoria',
            'marca',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Producto actualizado correctamente.',
            'data' => $producto,
        ]);
    }


    public function cambiarEstado(int $id): JsonResponse
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado.',
            ], 404);
        }

        $producto->activo = !$producto->activo;
        $producto->save();

        return response()->json([
            'success' => true,
            'message' => $producto->activo
                ? 'Producto activado.'
                : 'Producto desactivado.',
            'data' => $producto,
        ]);
    }
}