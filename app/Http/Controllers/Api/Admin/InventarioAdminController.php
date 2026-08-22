<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventario;
use App\Models\Producto;
use App\Models\KardexCab;
use App\Models\DetalleKardex;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventarioAdminController extends Controller
{
    /**
     * Listado de inventarios.
     *
     * Permite filtrar por:
     * - producto
     * - almacen
     * - local
     * - vendedor
     */
    public function index(Request $request): JsonResponse
    {
        $query = Inventario::with([
            'producto',
            'almacen',
            'local',
            'vendedor',
        ]);

        if ($request->filled('producto_id')) {
            $query->where(
                'producto_id',
                $request->producto_id
            );
        }

        if ($request->filled('almacen_id')) {
            $query->where(
                'almacen_id',
                $request->almacen_id
            );
        }

        if ($request->filled('local_id')) {
            $query->where(
                'local_id',
                $request->local_id
            );
        }

        if ($request->filled('vendedor_id')) {
            $query->where(
                'vendedor_id',
                $request->vendedor_id
            );
        }

        $inventarios = $query
            ->orderByDesc('id')
            ->paginate(
                $request->integer('per_page', 20)
            );

        return response()->json([
            'success' => true,
            'data' => $inventarios,
        ]);
    }


    /**
     * Mostrar un inventario específico.
     */
    public function show(int $id): JsonResponse
    {
        $inventario = Inventario::with([
            'producto',
            'almacen',
            'local',
            'vendedor',
            'reservas',
        ])->find($id);

        if (!$inventario) {
            return response()->json([
                'success' => false,
                'message' => 'Inventario no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $inventario,
        ]);
    }


    /**
     * Buscar productos para INGRESOS.
     *
     * Busca por código o descripción.
     */
    public function buscarProductos(Request $request): JsonResponse
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

        $productos = Producto::query()
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
            ->get([
                'id',
                'codigo',
                'descripcion',
                'precio',
                'stock',
                'activo',
            ]);

        return response()->json([
            'success' => true,
            'data' => $productos,
        ]);
    }


    /**
     * Buscar inventarios para EGRESOS.
     *
     * La búsqueda se realiza sobre Producto,
     * pero devuelve el inventario concreto.
     */
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

        $query = Inventario::with([
            'producto:id,codigo,descripcion,precio,activo',
            'almacen',
            'local',
            'vendedor',
        ])
        ->whereHas('producto', function ($query) use ($buscar) {
            $query
                ->where('activo', true)
                ->where(function ($q) use ($buscar) {
                    $q
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
                });
        });

        if ($request->filled('almacen_id')) {
            $query->where(
                'almacen_id',
                $request->almacen_id
            );
        }

        if ($request->filled('local_id')) {
            $query->where(
                'local_id',
                $request->local_id
            );
        }

        if ($request->filled('vendedor_id')) {
            $query->where(
                'vendedor_id',
                $request->vendedor_id
            );
        }

        $inventarios = $query
            ->orderBy('id')
            ->limit(50)
            ->get();

        $inventarios->each(function ($inventario) {
            $inventario->saldo_disponible =
                $inventario->cantidad -
                $inventario->cantidad_reservada;
        });

        return response()->json([
            'success' => true,
            'data' => $inventarios,
        ]);
    }


    /**
     * Registrar un INGRESO.
     */
    public function ingreso(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'id_almacen' => [
                'required',
                'integer',
                'exists:almacenes,id',
            ],

            'id_local' => [
                'nullable',
                'integer',
                'exists:locales,id',
            ],

            'id_vendedor' => [
                'nullable',
                'integer',
                'exists:vendedores,id',
            ],

            'fecha' => [
                'nullable',
                'date',
            ],

            'observacion' => [
                'nullable',
                'string',
                'max:255',
            ],

            'detalles' => [
                'required',
                'array',
                'min:1',
            ],

            'detalles.*.id_producto' => [
                'required',
                'integer',
                'exists:productos,id',
            ],

            'detalles.*.cantidad' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'detalles.*.precio_unitario' => [
                'nullable',
                'numeric',
                'gte:0',
            ],

            'detalles.*.observacion' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        return DB::transaction(function () use ($datos) {

            $usuarioId = auth()->id();

            if (!$usuarioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado.',
                ], 401);
            }

            /*
             * Crear cabecera.
             */
            $numero = $this->siguienteNumero();

            $kardex = KardexCab::create([
                'numero' => $numero,
                'id_almacen' => $datos['id_almacen'],
                'id_local' => $datos['id_local'] ?? null,
                'id_vendedor' => $datos['id_vendedor'] ?? null,
                'fecha' => $datos['fecha'] ?? now(),
                'tipo_transaccion' => 'INGRESO',
                'id_usuario' => $usuarioId,
                'observacion' => $datos['observacion'] ?? null,
                'activo' => true,
            ]);

            foreach ($datos['detalles'] as $detalle) {

                $producto = Producto::findOrFail(
                    $detalle['id_producto']
                );

                /*
                 * La combinación es UNIQUE:
                 *
                 * producto + almacen + local + vendedor
                 */
                $inventario = Inventario::where(
                    'producto_id',
                    $producto->id
                )
                ->where(
                    'almacen_id',
                    $datos['id_almacen']
                )
                ->where(
                    'local_id',
                    $datos['id_local'] ?? null
                )
                ->where(
                    'vendedor_id',
                    $datos['id_vendedor'] ?? null
                )
                ->lockForUpdate()
                ->first();

                /*
                 * Si no existe, se crea.
                 */
                if (!$inventario) {

                    $inventario = Inventario::create([
                        'producto_id' => $producto->id,
                        'almacen_id' => $datos['id_almacen'],
                        'local_id' => $datos['id_local'] ?? null,
                        'vendedor_id' => $datos['id_vendedor'] ?? null,
                        'cantidad' => 0,
                        'cantidad_reservada' => 0,
                    ]);
                }

                $cantidadAnterior =
                    (float) $inventario->cantidad;

                $reservadaAnterior =
                    (float) $inventario->cantidad_reservada;

                $disponibleAnterior =
                    $cantidadAnterior -
                    $reservadaAnterior;

                $cantidadIngreso =
                    (float) $detalle['cantidad'];

                $cantidadNueva =
                    $cantidadAnterior +
                    $cantidadIngreso;

                $disponibleNuevo =
                    $cantidadNueva -
                    $reservadaAnterior;

                /*
                 * Actualizar inventario.
                 */
                $inventario->cantidad =
                    $cantidadNueva;

                $inventario->save();

                /*
                 * Registrar detalle Kardex.
                 */
                DetalleKardex::create([
                    'id_kardex_cab' => $kardex->id,
                    'id_producto' => $producto->id,
                    'id_inventario' => $inventario->id,

                    'cantidad' => $cantidadIngreso,

                    'cantidad_anterior' =>
                        $cantidadAnterior,

                    'cantidad_nueva' =>
                        $cantidadNueva,

                    'cantidad_reservada_anterior' =>
                        $reservadaAnterior,

                    'cantidad_reservada_nueva' =>
                        $reservadaAnterior,

                    'saldo_disponible_anterior' =>
                        $disponibleAnterior,

                    'saldo_disponible_nuevo' =>
                        $disponibleNuevo,

                    'precio_unitario' =>
                        $detalle['precio_unitario'] ?? null,

                    'observacion' =>
                        $detalle['observacion'] ?? null,
                ]);
            }

            $kardex->load([
                'almacen',
                'local',
                'vendedor',
                'usuario',
                'detalles.producto',
                'detalles.inventario',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ingreso registrado correctamente.',
                'data' => $kardex,
            ], 201);
        });
    }


    /**
     * Registrar un EGRESO.
     */
    public function egreso(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'id_almacen' => [
                'required',
                'integer',
                'exists:almacenes,id',
            ],

            'id_local' => [
                'nullable',
                'integer',
                'exists:locales,id',
            ],

            'id_vendedor' => [
                'nullable',
                'integer',
                'exists:vendedores,id',
            ],

            'fecha' => [
                'nullable',
                'date',
            ],

            'observacion' => [
                'nullable',
                'string',
                'max:255',
            ],

            'detalles' => [
                'required',
                'array',
                'min:1',
            ],

            'detalles.*.id_producto' => [
                'required',
                'integer',
                'exists:productos,id',
            ],

            'detalles.*.cantidad' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'detalles.*.precio_unitario' => [
                'nullable',
                'numeric',
                'gte:0',
            ],

            'detalles.*.observacion' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        return DB::transaction(function () use ($datos) {

            $usuarioId = auth()->id();

            if (!$usuarioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado.',
                ], 401);
            }

            $numero = $this->siguienteNumero();

            $kardex = KardexCab::create([
                'numero' => $numero,
                'id_almacen' => $datos['id_almacen'],
                'id_local' => $datos['id_local'] ?? null,
                'id_vendedor' => $datos['id_vendedor'] ?? null,
                'fecha' => $datos['fecha'] ?? now(),
                'tipo_transaccion' => 'EGRESO',
                'id_usuario' => $usuarioId,
                'observacion' => $datos['observacion'] ?? null,
                'activo' => true,
            ]);

            foreach ($datos['detalles'] as $detalle) {

                $producto = Producto::findOrFail(
                    $detalle['id_producto']
                );

                /*
                 * Buscar el inventario exacto.
                 */
                $inventario = Inventario::where(
                    'producto_id',
                    $producto->id
                )
                ->where(
                    'almacen_id',
                    $datos['id_almacen']
                )
                ->where(
                    'local_id',
                    $datos['id_local'] ?? null
                )
                ->where(
                    'vendedor_id',
                    $datos['id_vendedor'] ?? null
                )
                ->lockForUpdate()
                ->first();

                if (!$inventario) {
                    throw ValidationException::withMessages([
                        'detalles' => [
                            "No existe inventario para el producto {$producto->codigo} en la ubicación seleccionada."
                        ],
                    ]);
                }

                $cantidadAnterior =
                    (float) $inventario->cantidad;

                $reservadaAnterior =
                    (float) $inventario->cantidad_reservada;

                $disponibleAnterior =
                    $cantidadAnterior -
                    $reservadaAnterior;

                $cantidadEgreso =
                    (float) $detalle['cantidad'];

                /*
                 * No permitir sacar más de lo disponible.
                 */
                if ($cantidadEgreso > $disponibleAnterior) {
                    throw ValidationException::withMessages([
                        'detalles' => [
                            "Stock insuficiente para el producto {$producto->codigo}. Disponible: {$disponibleAnterior}."
                        ],
                    ]);
                }

                $cantidadNueva =
                    $cantidadAnterior -
                    $cantidadEgreso;

                $disponibleNuevo =
                    $cantidadNueva -
                    $reservadaAnterior;

                /*
                 * Actualizar inventario.
                 */
                $inventario->cantidad =
                    $cantidadNueva;

                $inventario->save();

                /*
                 * Registrar Kardex.
                 */
                DetalleKardex::create([
                    'id_kardex_cab' => $kardex->id,
                    'id_producto' => $producto->id,
                    'id_inventario' => $inventario->id,

                    'cantidad' => $cantidadEgreso,

                    'cantidad_anterior' =>
                        $cantidadAnterior,

                    'cantidad_nueva' =>
                        $cantidadNueva,

                    'cantidad_reservada_anterior' =>
                        $reservadaAnterior,

                    'cantidad_reservada_nueva' =>
                        $reservadaAnterior,

                    'saldo_disponible_anterior' =>
                        $disponibleAnterior,

                    'saldo_disponible_nuevo' =>
                        $disponibleNuevo,

                    'precio_unitario' =>
                        $detalle['precio_unitario'] ?? null,

                    'observacion' =>
                        $detalle['observacion'] ?? null,
                ]);
            }

            $kardex->load([
                'almacen',
                'local',
                'vendedor',
                'usuario',
                'detalles.producto',
                'detalles.inventario',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Egreso registrado correctamente.',
                'data' => $kardex,
            ], 201);
        });
    }


    /**
     * Mostrar una nota Kardex.
     */
    public function kardex(int $id): JsonResponse
    {
        $kardex = KardexCab::with([
            'almacen',
            'local',
            'vendedor',
            'usuario',
            'usuarioAnulacion',
            'detalles.producto',
            'detalles.inventario',
        ])->find($id);

        if (!$kardex) {
            return response()->json([
                'success' => false,
                'message' => 'Movimiento Kardex no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $kardex,
        ]);
    }


    /**
     * Anular un movimiento Kardex.
     */
    public function anular(Request $request, int $id): JsonResponse
    {
        $datos = $request->validate([
            'motivo_anulacion' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        return DB::transaction(function () use (
            $datos,
            $id
        ) {

            $usuarioId = auth()->id();

            if (!$usuarioId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado.',
                ], 401);
            }

            $kardex = KardexCab::with('detalles')
                ->lockForUpdate()
                ->find($id);

            if (!$kardex) {
                return response()->json([
                    'success' => false,
                    'message' => 'Movimiento Kardex no encontrado.',
                ], 404);
            }

            if (!$kardex->activo) {
                return response()->json([
                    'success' => false,
                    'message' => 'El movimiento ya se encuentra anulado.',
                ], 422);
            }

            foreach ($kardex->detalles as $detalle) {

                $inventario = Inventario::lockForUpdate()
                    ->find($detalle->id_inventario);

                if (!$inventario) {
                    throw ValidationException::withMessages([
                        'inventario' => [
                            "No se encontró el inventario {$detalle->id_inventario}."
                        ],
                    ]);
                }

                $cantidadActual =
                    (float) $inventario->cantidad;

                $cantidadMovimiento =
                    (float) $detalle->cantidad;

                /*
                 * Revertir el movimiento.
                 */
                if ($kardex->tipo_transaccion === 'INGRESO') {

                    if (
                        $cantidadActual <
                        $cantidadMovimiento
                    ) {
                        throw ValidationException::withMessages([
                            'inventario' => [
                                "No se puede anular el ingreso porque el stock actual es menor que la cantidad que se debe revertir."
                            ],
                        ]);
                    }

                    $inventario->cantidad =
                        $cantidadActual -
                        $cantidadMovimiento;

                } elseif (
                    $kardex->tipo_transaccion === 'EGRESO'
                ) {

                    $inventario->cantidad =
                        $cantidadActual +
                        $cantidadMovimiento;

                } else {

                    throw ValidationException::withMessages([
                        'tipo_transaccion' => [
                            'Tipo de transacción no soportado para anulación.'
                        ],
                    ]);
                }

                /*
                 * No permitir stock disponible negativo.
                 */
                if (
                    $inventario->cantidad <
                    $inventario->cantidad_reservada
                ) {
                    throw ValidationException::withMessages([
                        'inventario' => [
                            'La anulación produciría un stock disponible negativo.'
                        ],
                    ]);
                }

                $inventario->save();
            }

            $kardex->update([
                'activo' => false,
                'fecha_anulacion' => now(),
                'id_usuario_anulacion' => $usuarioId,
                'motivo_anulacion' =>
                    $datos['motivo_anulacion'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Movimiento anulado correctamente.',
                'data' => $kardex->fresh([
                    'almacen',
                    'local',
                    'vendedor',
                    'usuario',
                    'usuarioAnulacion',
                    'detalles.producto',
                    'detalles.inventario',
                ]),
            ]);
        });
    }


    /**
     * Obtener el siguiente número de Kardex.
     */
    private function siguienteNumero(): int
    {
        $ultimo = KardexCab::lockForUpdate()
            ->max('numero');

        return ((int) $ultimo) + 1;
    }
}