<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventario;
use App\Models\KardexCab;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventarioAdminController extends Controller
{
    /**
     * GET /api/admin/inventarios
     *
     * Consulta el inventario actual.
     */
    public function index(Request $request): JsonResponse
    {
        $items = Inventario::with([
            'producto',
            'almacen',
        ])
            ->when(
                $request->filled('almacen_id'),
                fn ($q) =>
                    $q->where(
                        'almacen_id',
                        $request->input('almacen_id')
                    )
            )
            ->when(
                $request->filled('q'),
                function ($query) use ($request) {

                    $texto = $request->input('q');

                    $query->whereHas(
                        'producto',
                        function ($q) use ($texto) {

                            $q->where(
                                'codigo',
                                'like',
                                "%{$texto}%"
                            )
                            ->orWhere(
                                'descripcion',
                                'like',
                                "%{$texto}%"
                            );
                        }
                    );
                }
            )
            ->orderBy('id')
            ->get()
            ->map(
                fn (Inventario $inventario) =>
                    $this->formatoInventario($inventario)
            );

        return response()->json([
            'data' => $items,
        ]);
    }


    /**
     * POST /api/admin/inventarios/movimiento
     *
     * Registra un ingreso o egreso completo.
     *
     * Una petición puede contener varios productos.
     */
    public function registrarMovimiento(
        Request $request
    ): JsonResponse {

        $datos = $request->validate([

            'id_almacen' => [
                'required',
                'integer',
                'exists:almacenes,id',
            ],

            'tipo_transaccion' => [
                'required',
                Rule::in([
                    'ingreso',
                    'egreso',
                ]),
            ],

            'observacion' => [
                'nullable',
                'string',
                'max:255',
            ],

            'productos' => [
                'required',
                'array',
                'min:1',
            ],

            'productos.*.id_producto' => [
                'required',
                'integer',
                'exists:productos,id',
            ],

            'productos.*.cantidad' => [
                'required',
                'numeric',
                'min:0.01',
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | Evitar productos repetidos
        |--------------------------------------------------------------------------
        */

        $idsProductos = collect(
            $datos['productos']
        )->pluck('id_producto');

        if ($idsProductos->count() !== $idsProductos->unique()->count()) {

            throw ValidationException::withMessages([
                'productos' =>
                    'No se puede repetir un producto dentro del mismo movimiento.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | TRANSACCIÓN
        |--------------------------------------------------------------------------
        */

        $kardex = DB::transaction(function () use ($datos) {

            /*
            |--------------------------------------------------------------------------
            | 1. Crear cabecera
            |--------------------------------------------------------------------------
            */

            $kardex = KardexCab::create([

                'id_almacen' =>
                    $datos['id_almacen'],

                'fecha' =>
                    now(),

                'tipo_transaccion' =>
                    $datos['tipo_transaccion'],

                'id_usuario' =>
                    Auth::id(),

                'activo' =>
                    true,

                'observacion' =>
                    $datos['observacion'] ?? null,
            ]);


            /*
            |--------------------------------------------------------------------------
            | 2. Procesar productos
            |--------------------------------------------------------------------------
            */

            foreach ($datos['productos'] as $item) {

                /*
                |--------------------------------------------------------------------------
                | Buscar inventario y bloquear fila
                |--------------------------------------------------------------------------
                */

                $inventario = Inventario::where(
                    'almacen_id',
                    $datos['id_almacen']
                )
                ->where(
                    'producto_id',
                    $item['id_producto']
                )
                ->lockForUpdate()
                ->first();


                /*
                |--------------------------------------------------------------------------
                | INGRESO
                |--------------------------------------------------------------------------
                */

                if (
                    $datos['tipo_transaccion']
                    === 'ingreso'
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Si todavía no existe inventario,
                    | se crea automáticamente.
                    |--------------------------------------------------------------------------
                    */

                    if (!$inventario) {

                        $inventario = Inventario::create([

                            'producto_id' =>
                                $item['id_producto'],

                            'almacen_id' =>
                                $datos['id_almacen'],

                            'local_id' =>
                                null,

                            'cantidad' =>
                                0,

                            'cantidad_reservada' =>
                                0,

                            'usuario_id' =>
                                Auth::id(),
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Aumentar existencia
                    |--------------------------------------------------------------------------
                    */

                    $inventario->increment(
                        'cantidad',
                        $item['cantidad']
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | EGRESO
                |--------------------------------------------------------------------------
                */

                else {

                    /*
                    |--------------------------------------------------------------------------
                    | El inventario debe existir.
                    |--------------------------------------------------------------------------
                    */

                    if (!$inventario) {

                        throw ValidationException::withMessages([
                            'productos' =>
                                "El producto {$item['id_producto']} no existe en el inventario del almacén seleccionado.",
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Stock disponible
                    |--------------------------------------------------------------------------
                    */

                    $stockDisponible =
                        $inventario->cantidad
                        -
                        $inventario->cantidad_reservada;


                    /*
                    |--------------------------------------------------------------------------
                    | Validar stock
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $stockDisponible
                        < $item['cantidad']
                    ) {

                        throw ValidationException::withMessages([
                            'productos' =>
                                "Stock insuficiente para el producto {$item['id_producto']}. Disponible: {$stockDisponible}.",
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Descontar existencia
                    |--------------------------------------------------------------------------
                    */

                    $inventario->decrement(
                        'cantidad',
                        $item['cantidad']
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Actualizar usuario responsable
                |--------------------------------------------------------------------------
                */

                $inventario->update([
                    'usuario_id' => Auth::id(),
                ]);


                /*
                |--------------------------------------------------------------------------
                | 3. Crear detalle del Kardex
                |--------------------------------------------------------------------------
                */

                $kardex->detalles()->create([

                    'id_producto' =>
                        $item['id_producto'],

                    'cantidad' =>
                        $item['cantidad'],
                ]);
            }


            return $kardex;
        });


        /*
        |--------------------------------------------------------------------------
        | Respuesta
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'message' =>
                'Movimiento registrado correctamente.',

            'data' =>
                $kardex->load([
                    'almacen',
                    'usuario',
                    'detalles.producto',
                ]),

        ], 201);
    }


    /**
     * GET /api/admin/inventarios/{inventario}
     */
    public function show(
        Inventario $inventario
    ): JsonResponse {

        return response()->json([
            'data' =>
                $this->formatoInventario(
                    $inventario->load([
                        'producto',
                        'almacen',
                    ])
                ),
        ]);
    }


    /**
     * Formato utilizado por InventarioView.vue
     */
    private function formatoInventario(
        Inventario $inventario
    ): array {

        return [

            'id' =>
                $inventario->id,

            'producto_id' =>
                $inventario->producto_id,

            'codigo' =>
                $inventario->producto?->codigo,

            'nombre' =>
                $inventario->producto?->descripcion,

            'almacen' =>
                $inventario->almacen
                    ? [
                        'id' =>
                            $inventario->almacen->id,

                        'nombre' =>
                            $inventario->almacen->nombre,
                    ]
                    : null,

            'stock_disponible' =>
                $inventario->cantidad
                -
                $inventario->cantidad_reservada,

            'cantidad' =>
                $inventario->cantidad,

            'cantidad_reservada' =>
                $inventario->cantidad_reservada,

            'stock_minimo' =>
                $inventario->producto?->stock_minimo ?? 0,
        ];
    }

    public function buscarProductos(Request $request)
{
    $request->validate([
        'almacen_id' => 'required|integer',
        'buscar'     => 'required|string|min:2|max:100',
    ]);

    $almacenId = $request->almacen_id;
    $buscar    = trim($request->buscar);

    $productos = DB::table('inventarios')
        ->join(
            'productos',
            'productos.id',
            '=',
            'inventarios.producto_id'
        )
        ->where(
            'inventarios.almacen_id',
            $almacenId
        )
        ->where(function ($query) use ($buscar) {

            $query->where(
                'productos.codigo',
                'LIKE',
                '%' . $buscar . '%'
            )

            ->orWhere(
                'productos.nombre',
                'LIKE',
                '%' . $buscar . '%'
            )

            ->orWhere(
                'productos.descripcion',
                'LIKE',
                '%' . $buscar . '%'
            );

        })
        ->select([
            'inventarios.producto_id',
            'productos.codigo',
            'productos.nombre',
            'productos.descripcion',
            'inventarios.stock as stock_disponible',
            'productos.precio_venta as precio',
        ])
        ->orderBy('productos.nombre')
        ->limit(50)
        ->get();

    return response()->json([
        'success' => true,
        'data' => $productos
    ]);
}
}