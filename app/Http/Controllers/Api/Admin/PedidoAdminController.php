<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pedido;
use App\Models\PedidoReserva;
use App\Models\Inventario;
use App\Models\KardexCab;
use App\Models\DetalleKardex;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PedidoAdminController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LISTAR PEDIDOS
    |--------------------------------------------------------------------------
    */

    public function index(Request $request): JsonResponse
    {
        $query = Pedido::with([
            'detalles.producto',
            'reservas.producto',
            'reservas.producto',
            'reservas.almacen',
            'reservas.local',
            'reservas.vendedor',
        ]);

        if ($request->filled('estado')) {
            $query->where('estado', strtolower($request->estado));
        }

        if ($request->filled('buscar')) {

            $buscar = trim($request->buscar);

            $query->where(function ($q) use ($buscar) {

                $q->where(
                    'nombre_cliente',
                    'LIKE',
                    "%{$buscar}%"
                )
                ->orWhere(
                    'celular_whatsapp',
                    'LIKE',
                    "%{$buscar}%"
                );
            });
        }

        if ($request->filled('fecha_desde')) {

            $query->whereDate(
                'fecha_pedido',
                '>=',
                $request->fecha_desde
            );
        }

        if ($request->filled('fecha_hasta')) {

            $query->whereDate(
                'fecha_pedido',
                '<=',
                $request->fecha_hasta
            );
        }

        $pedidos = $query
            ->orderByDesc('fecha_pedido')
            ->paginate(
                $request->integer('per_page', 20)
            );

        return response()->json([
            'success' => true,
            'data' => $pedidos,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | MOSTRAR PEDIDO
    |--------------------------------------------------------------------------
    */

    public function show(int $id): JsonResponse
    {
        $pedido = Pedido::with([
            'detalles.producto',
            'reservas.producto',
            'reservas.producto',
            'reservas.almacen',
            'reservas.local',
            'reservas.vendedor',
        ])->find($id);

        if (!$pedido) {

            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $pedido,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR ESTADO
    |--------------------------------------------------------------------------
    |
    | IMPORTANTE:
    | La confirmación REAL se realiza mediante:
    |
    | POST /admin/pedidos/{id}/confirmar
    |
    | Por eso este método NO debe crear reservas.
    |
    */

    public function actualizarEstado(
        Request $request,
        Pedido $pedido
    ): JsonResponse {

        $datos = $request->validate([
            'estado' => [
                'required',
                'in:pendiente,confirmado,preparando,listo,entregado,cancelado'
            ],
        ]);

        $nuevoEstado = strtolower($datos['estado']);
        $estadoActual = strtolower($pedido->estado);

        /*
        |--------------------------------------------------------------------------
        | CONFIRMAR
        |--------------------------------------------------------------------------
        |
        | La confirmación debe hacerse por /confirmar porque necesita
        | seleccionar inventarios.
        |
        */

        if ($nuevoEstado === 'confirmado') {

            throw ValidationException::withMessages([
                'estado' => [
                    'Para confirmar el pedido debe utilizar el proceso de asignación de inventario.'
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | ENTREGAR
        |--------------------------------------------------------------------------
        */

        if ($nuevoEstado === 'entregado') {

            throw ValidationException::withMessages([
                'estado' => [
                    'Para entregar el pedido debe utilizar el proceso de entrega.'
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | CANCELAR
        |--------------------------------------------------------------------------
        */

        if ($nuevoEstado === 'cancelado') {

            return $this->cancelar(
                $request,
                $pedido->id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ACTUALIZAR ESTADOS INTERMEDIOS
        |--------------------------------------------------------------------------
        */

        $pedido->update([
            'estado' => $nuevoEstado
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado correctamente.',
            'data' => $pedido->fresh([
                'detalles.producto',
                'reservas.producto',
                'reservas.producto',
                'reservas.almacen',
                'reservas.local',
                'reservas.vendedor',
            ]),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | DISPONIBILIDAD
    |--------------------------------------------------------------------------
    */

    public function disponibilidad(int $id): JsonResponse
    {
        $pedido = Pedido::with([
            'detalles.producto',
            'reservas'
        ])->find($id);

        if (!$pedido) {

            return response()->json([
                'success' => false,
                'message' => 'Pedido no encontrado.',
            ], 404);
        }

        $productos = $pedido->detalles->map(
            function ($detalle) use ($pedido) {

                /*
                |--------------------------------------------------------------------------
                | RESERVADO PARA ESTE PRODUCTO
                |--------------------------------------------------------------------------
                */

                $cantidadReservada = $pedido->reservas
                    ->where('estado', 'activa')
                    ->where(
                        'producto_id',
                        $detalle->producto_id
                    )
                    ->sum('cantidad');


                /*
                |--------------------------------------------------------------------------
                | INVENTARIOS DISPONIBLES
                |--------------------------------------------------------------------------
                */

                $inventarios = Inventario::with([
                    'almacen',
                    'local',
                    'vendedor'
                ])
                ->where(
                    'producto_id',
                    $detalle->producto_id
                )
                ->get()
                ->map(function ($inv) {

                    $stock = (float) $inv->cantidad;

                    $reservado = (float) $inv->cantidad_reservada;

                    return [
                        'inventario_id' => $inv->id,

                        'producto_id' => $inv->producto_id,

                        'almacen_id' => $inv->almacen_id,

                        'almacen' => $inv->almacen?->nombre,

                        'local_id' => $inv->local_id,

                        'vendedor_id' => $inv->vendedor_id,

                        'vendedor' => $inv->vendedor?->nombre,

                        'stock' => $stock,

                        'reservado' => $reservado,

                        'disponible' => max(
                            0,
                            $stock - $reservado
                        ),
                    ];
                });


                return [
                    'detalle_id' => $detalle->id,

                    'producto_id' => $detalle->producto_id,

                    'codigo' => $detalle->codigo_producto,

                    'nombre' => $detalle->descripcion_producto,

                    'cantidad_pedido' => (float) $detalle->cantidad,

                    'cantidad_reservada' => (float) $cantidadReservada,

                    'cantidad_faltante' => max(
                        0,
                        (float) $detalle->cantidad
                        - (float) $cantidadReservada
                    ),

                    'almacenes' => $inventarios,
                ];
            }
        );

        return response()->json([
            'success' => true,

            'data' => [
                'pedido_id' => $pedido->id,

                'estado' => strtolower($pedido->estado),

                'productos' => $productos,
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CONFIRMAR PEDIDO
    |--------------------------------------------------------------------------
    |
    | Aquí:
    |
    | 1. Se seleccionan los inventarios.
    | 2. Se crean las reservas.
    | 3. Se incrementa cantidad_reservada.
    | 4. NO se descuenta cantidad.
    |
    */

    public function confirmar(
        Request $request,
        int $id
    ): JsonResponse {

        $datos = $request->validate([

            'vendedor_id' => [
                'required',
                'integer',
                'exists:vendedores,id',
            ],

            'reservas' => [
                'required',
                'array',
                'min:1'
            ],

            'reservas.*.detalle_id' => [
                'required',
                'integer'
            ],

            'reservas.*.inventario_id' => [
                'required',
                'integer',
                'exists:inventarios,id'
            ],

            'reservas.*.cantidad' => [
                'required',
                'numeric',
                'gt:0'
            ],            

        ]);


        return DB::transaction(
            function () use ($id, $datos) {

                /*
                |--------------------------------------------------------------------------
                | BLOQUEAR PEDIDO
                |--------------------------------------------------------------------------
                */

                $pedido = Pedido::with('detalles')
                    ->lockForUpdate()
                    ->find($id);

                if (!$pedido) {

                    return response()->json([
                        'success' => false,
                        'message' => 'Pedido no encontrado.',
                    ], 404);
                }


                /*
                |--------------------------------------------------------------------------
                | SOLO PENDIENTES
                |--------------------------------------------------------------------------
                */

                if (
                    strtolower($pedido->estado)
                    !== 'pendiente'
                ) {

                    throw ValidationException::withMessages([
                        'estado' => [
                            'Solo se pueden confirmar pedidos pendientes.'
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | AGRUPAR POR DETALLE
                |--------------------------------------------------------------------------
                */

                $reservasPorDetalle = collect(
                    $datos['reservas']
                )->groupBy('detalle_id');


                /*
                |--------------------------------------------------------------------------
                | VERIFICAR TODAS LAS LÍNEAS
                |--------------------------------------------------------------------------
                */

                foreach ($pedido->detalles as $detalle) {

                    $reservasDeEstaLinea =
                        $reservasPorDetalle->get(
                            $detalle->id,
                            collect()
                        );

                    if (
                        $reservasDeEstaLinea->isEmpty()
                    ) {

                        throw ValidationException::withMessages([
                            'reservas' => [
                                "Falta asignar almacén(es) para {$detalle->descripcion_producto}."
                            ],
                        ]);
                    }


                    $sumaReservada =
                        $reservasDeEstaLinea->sum('cantidad');


                    if (
                        abs(
                            (float) $detalle->cantidad
                            -
                            (float) $sumaReservada
                        ) > 0.0001
                    ) {

                        throw ValidationException::withMessages([
                            'reservas' => [
                                "La suma reservada para {$detalle->descripcion_producto} debe ser exactamente {$detalle->cantidad}. Actual: {$sumaReservada}."
                            ],
                        ]);
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | VALIDAR DETALLES
                |--------------------------------------------------------------------------
                */

                $idsDetallesPedido =
                    $pedido->detalles->pluck('id');

                $idsEnviados =
                    collect($datos['reservas'])
                    ->pluck('detalle_id')
                    ->unique();

                if (
                    $idsEnviados
                    ->diff($idsDetallesPedido)
                    ->isNotEmpty()
                ) {

                    throw ValidationException::withMessages([
                        'reservas' => [
                            'Alguna reserva no corresponde a un detalle de este pedido.'
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | PROCESAR RESERVAS
                |--------------------------------------------------------------------------
                */

                foreach (
                    $datos['reservas']
                    as $reservaData
                ) {

                    $detalle =
                        $pedido->detalles
                        ->firstWhere(
                            'id',
                            $reservaData['detalle_id']
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | BLOQUEAR INVENTARIO
                    |--------------------------------------------------------------------------
                    */

                    $inventario =
                        Inventario::with([
                            'producto',
                            'almacen',
                            'local',
                            'vendedor'
                        ])
                        ->lockForUpdate()
                        ->find(
                            $reservaData['inventario_id']
                        );


                    if (!$inventario) {

                        throw ValidationException::withMessages([
                            'inventario' => [
                                'El inventario seleccionado no existe.'
                            ],
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | VALIDAR PRODUCTO
                    |--------------------------------------------------------------------------
                    */

                    if (
                        (int) $inventario->producto_id
                        !==
                        (int) $detalle->producto_id
                    ) {

                        throw ValidationException::withMessages([
                            'inventario' => [
                                "El inventario seleccionado no corresponde a {$detalle->descripcion_producto}."
                            ],
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | VALIDAR STOCK DISPONIBLE
                    |--------------------------------------------------------------------------
                    */

                    $cantidadReserva =
                        (float) $reservaData['cantidad'];

                    $stock =
                        (float) $inventario->cantidad;

                    $reservado =
                        (float) $inventario->cantidad_reservada;

                    $disponible =
                        $stock - $reservado;


                    if (
                        $cantidadReserva
                        >
                        $disponible
                    ) {

                        throw ValidationException::withMessages([
                            'inventario' => [
                                "Stock insuficiente en {$inventario->almacen?->nombre} para {$detalle->descripcion_producto}. Disponible: {$disponible}."
                            ],
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | CREAR RESERVA
                    |--------------------------------------------------------------------------
                    |
                    | IMPORTANTE:
                    | Guardamos inventario_id.
                    |
                    */

                    PedidoReserva::create([

                        'pedido_id' =>
                            $pedido->id,

                        'producto_id' =>
                            $inventario->producto_id,

                        /*'inventario_id' =>
                            $inventario->id,*/

                        'almacen_id' =>
                            $inventario->almacen_id,

                        'local_id' =>
                            $inventario->almacen->local_id,

                        'vendedor_id' =>
                            $datos['vendedor_id'],

                        'cantidad' =>
                            $cantidadReserva,

                        'estado' =>
                            'activa',
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | AUMENTAR RESERVADO
                    |--------------------------------------------------------------------------
                    */

                    $inventario->increment(
                        'cantidad_reservada',
                        $cantidadReserva
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | CONFIRMAR PEDIDO
                |--------------------------------------------------------------------------
                */
                $pedido->vendedor_id = $datos['vendedor_id'];
                $pedido->estado =
                    'confirmado';

                $pedido->save();


                /*
                |--------------------------------------------------------------------------
                | RESPUESTA
                |--------------------------------------------------------------------------
                */

                $pedido->load([
                    'detalles.producto',
                    'reservas.producto',
                    'reservas.producto',
                    'reservas.almacen',
                    'reservas.local',
                    'reservas.vendedor',
                ]);


                return response()->json([

                    'success' => true,

                    'message' =>
                        'Pedido confirmado y cantidades reservadas correctamente.',

                    'data' => $pedido,
                ]);
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CANCELAR PEDIDO
    |--------------------------------------------------------------------------
    */

    public function cancelar(
        Request $request,
        int $id
    ): JsonResponse {

        $datos = $request->validate([

            'motivo' => [
                'nullable',
                'string',
                'max:255'
            ],

        ]);


        return DB::transaction(
            function () use ($id, $datos) {

                $pedido = Pedido::with([
                    'reservas'
                ])
                ->lockForUpdate()
                ->find($id);


                if (!$pedido) {

                    return response()->json([
                        'success' => false,
                        'message' => 'Pedido no encontrado.',
                    ], 404);
                }


                $estado =
                    strtolower($pedido->estado);


                /*
                |--------------------------------------------------------------------------
                | NO SE PUEDE CANCELAR
                |--------------------------------------------------------------------------
                */

                if (
                    in_array(
                        $estado,
                        [
                            'entregado',
                            'cancelado'
                        ]
                    )
                ) {

                    throw ValidationException::withMessages([
                        'estado' => [
                            'El pedido no puede cancelarse en su estado actual.'
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | LIBERAR RESERVAS
                |--------------------------------------------------------------------------
                */

                foreach (
                    $pedido->reservas
                    as $reserva
                ) {

                    if (
                        strtolower($reserva->estado)
                        !==
                        'activa'
                    ) {
                        continue;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | INVENTARIO
                    |--------------------------------------------------------------------------
                    */

                    $inventario =
                        Inventario::lockForUpdate()
                           ->where('producto_id', $reserva->producto_id)
                            ->where('almacen_id', $reserva->almacen_id)
                            ->where('local_id', $reserva->local_id)
                            ->where('vendedor_id', $reserva->vendedor_id)
                            ->first();


                    if (!$inventario) {

                        throw ValidationException::withMessages([
                            'inventario' => [
                                'No se encontró el inventario asociado a la reserva.'
                            ],
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | VALIDAR RESERVA
                    |--------------------------------------------------------------------------
                    */

                    if (
                        (float) $inventario->cantidad_reservada
                        <
                        (float) $reserva->cantidad
                    ) {

                        throw ValidationException::withMessages([
                            'inventario' => [
                                'La cantidad reservada del inventario es inconsistente.'
                            ],
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | LIBERAR
                    |--------------------------------------------------------------------------
                    */

                    $inventario->decrement(
                        'cantidad_reservada',
                        $reserva->cantidad
                    );


                    $reserva->estado =
                        'liberada';

                    $reserva->save();
                }


                /*
                |--------------------------------------------------------------------------
                | MOTIVO
                |--------------------------------------------------------------------------
                */

                if (
                    !empty($datos['motivo'])
                ) {

                    $observacion =
                        $pedido->observaciones;

                    $nuevoTexto =
                        'Cancelado: '
                        .
                        $datos['motivo'];

                    $pedido->observaciones =
                        $observacion
                        ? $observacion . "\n" . $nuevoTexto
                        : $nuevoTexto;
                }


                /*
                |--------------------------------------------------------------------------
                | CANCELAR
                |--------------------------------------------------------------------------
                */

                $pedido->estado =
                    'cancelado';

                $pedido->save();


                return response()->json([

                    'success' => true,

                    'message' =>
                        'Pedido cancelado correctamente.',

                    'data' =>
                        $pedido->fresh([
                            'detalles.producto',
                            'reservas.producto',
                            'reservas.producto',
                            'reservas.almacen',
                            'reservas.local',
                            'reservas.vendedor',
                        ]),
                ]);
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ENTREGAR PEDIDO
    |--------------------------------------------------------------------------
    |
    | AQUÍ SE DESCUENTA EL STOCK.
    |
    | cantidad:
    |     100 -> 90
    |
    | cantidad_reservada:
    |     10 -> 0
    |
    | Además se crea Kardex.
    |
    */

    public function entregar(
        Request $request,
        int $id
    ): JsonResponse {

        return DB::transaction(
            function () use ($id) {

                $usuarioId =
                    auth()->id();


                /*
                |--------------------------------------------------------------------------
                | PEDIDO
                |--------------------------------------------------------------------------
                */

                $pedido =
                    Pedido::with([
                        'reservas',
                        'detalles',
                    ])
                    ->lockForUpdate()
                    ->find($id);


                if (!$pedido) {

                    return response()->json([
                        'success' => false,
                        'message' => 'Pedido no encontrado.',
                    ], 404);
                }


                /*
                |--------------------------------------------------------------------------
                | VALIDAR ESTADO
                |--------------------------------------------------------------------------
                */

                if (
                    strtolower($pedido->estado)
                    !==
                    'listo'
                ) {

                    throw ValidationException::withMessages([
                        'estado' => [
                            'Solo se pueden entregar pedidos confirmados.'
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | RESERVAS ACTIVAS
                |--------------------------------------------------------------------------
                */

                $reservasActivas =
                    $pedido->reservas
                        ->where(
                            'estado',
                            'activa'
                        );


                if (
                    $reservasActivas->isEmpty()
                ) {

                    throw ValidationException::withMessages([
                        'reservas' => [
                            'El pedido no tiene reservas activas para entregar.'
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | KARDEX POR UBICACIÓN
                |--------------------------------------------------------------------------
                */

                $kardexes = [];


                foreach (
                    $reservasActivas
                    as $reserva
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | INVENTARIO
                    |--------------------------------------------------------------------------
                    */

                    $inventario =
                        Inventario::with([
                            'producto',
                            'almacen',
                            'local',
                            'vendedor',
                        ])
                        ->lockForUpdate()
                        ->where('producto_id', $reserva->producto_id)
                        ->where('almacen_id', $reserva->almacen_id)
                        ->where('local_id', $reserva->local_id)
                        ->where('vendedor_id', $reserva->vendedor_id)
                        ->first();


                    if (!$inventario) {

                        throw ValidationException::withMessages([
                            'inventario' => [
                                 "No se encontró el inventario para el producto {$reserva->producto_id}, almacén {$reserva->almacen_id}, local {$reserva->local_id} y vendedor {$reserva->vendedor_id}."
                            ],
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | VALIDAR STOCK
                    |--------------------------------------------------------------------------
                    */

                    if (
                        (float) $inventario->cantidad
                        <
                        (float) $reserva->cantidad
                    ) {

                        throw ValidationException::withMessages([
                            'inventario' => [
                                "No existe stock suficiente para entregar {$inventario->producto->descripcion}."
                            ],
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | VALIDAR RESERVADO
                    |--------------------------------------------------------------------------
                    */

                    if (
                        (float) $inventario->cantidad_reservada
                        <
                        (float) $reserva->cantidad
                    ) {

                        throw ValidationException::withMessages([
                            'inventario' => [
                                'La cantidad reservada no coincide con el inventario.'
                            ],
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | CANTIDADES ANTERIORES
                    |--------------------------------------------------------------------------
                    */

                    $cantidadAnterior =
                        (float) $inventario->cantidad;

                    $reservadaAnterior =
                        (float) $inventario->cantidad_reservada;

                    $disponibleAnterior =
                        $cantidadAnterior
                        -
                        $reservadaAnterior;


                    /*
                    |--------------------------------------------------------------------------
                    | NUEVAS CANTIDADES
                    |--------------------------------------------------------------------------
                    */

                    $cantidadNueva =
                        $cantidadAnterior
                        -
                        (float) $reserva->cantidad;

                    $reservadaNueva =
                        $reservadaAnterior
                        -
                        (float) $reserva->cantidad;

                    $disponibleNuevo =
                        $cantidadNueva
                        -
                        $reservadaNueva;


                    /*
                    |--------------------------------------------------------------------------
                    | ACTUALIZAR INVENTARIO
                    |--------------------------------------------------------------------------
                    */

                    $inventario->cantidad =
                        $cantidadNueva;

                    $inventario->cantidad_reservada =
                        $reservadaNueva;

                    $inventario->save();


                    /*
                    |--------------------------------------------------------------------------
                    | CLAVE DE KARDEX
                    |--------------------------------------------------------------------------
                    */

                    $claveKardex =
                        implode(
                            '-',
                            [
                                $reserva->almacen_id ?? 0,
                                $reserva->local_id ?? 0,
                                $reserva->vendedor_id ?? 0,
                            ]
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | CREAR/OBTENER CABECERA KARDEX
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !isset(
                            $kardexes[$claveKardex]
                        )
                    ) {

                        $kardexes[$claveKardex] =
                            KardexCab::create([

                                'numero' =>
                                    $this->siguienteNumeroKardex(),

                                'id_almacen' =>
                                    $reserva->almacen_id,

                                'id_local' =>
                                    $reserva->local_id,

                                'id_vendedor' =>
                                    $reserva->vendedor_id,

                                'fecha' =>
                                    now(),

                                'tipo_transaccion' =>
                                    'EGRESO',

                                'id_usuario' =>
                                    $usuarioId,

                                'observacion' =>
                                    'Entrega pedido #' .
                                    $pedido->id,

                                'activo' =>
                                    true,
                            ]);
                    }


                    $kardex =
                        $kardexes[$claveKardex];


                    /*
                    |--------------------------------------------------------------------------
                    | DETALLE KARDEX
                    |--------------------------------------------------------------------------
                    */

                    DetalleKardex::create([

                        'id_kardex_cab' =>
                            $kardex->id,

                        'id_producto' =>
                            $inventario->producto_id,

                        'id_inventario' =>
                            $inventario->id,

                        'cantidad' =>
                            $reserva->cantidad,

                        'cantidad_anterior' =>
                            $cantidadAnterior,

                        'cantidad_nueva' =>
                            $cantidadNueva,

                        'cantidad_reservada_anterior' =>
                            $reservadaAnterior,

                        'cantidad_reservada_nueva' =>
                            $reservadaNueva,

                        'saldo_disponible_anterior' =>
                            $disponibleAnterior,

                        'saldo_disponible_nuevo' =>
                            $disponibleNuevo,

                        'precio_unitario' =>
                            $inventario->producto->precio,

                        'observacion' =>
                            'Entrega pedido #' .
                            $pedido->id,
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | RESERVA UTILIZADA
                    |--------------------------------------------------------------------------
                    */

                    $reserva->estado =
                        'confirmada';

                    $reserva->save();
                }


                /*
                |--------------------------------------------------------------------------
                | PEDIDO ENTREGADO
                |--------------------------------------------------------------------------
                */

                $pedido->estado =
                    'entregado';

                $pedido->save();


                /*
                |--------------------------------------------------------------------------
                | RESPUESTA
                |--------------------------------------------------------------------------
                */

                return response()->json([

                    'success' => true,

                    'message' =>
                        'Pedido entregado correctamente. El stock fue descontado y registrado en Kardex.',

                    'data' =>
                        $pedido->fresh([
                            'detalles.producto',
                            'reservas.producto',
                            'reservas.producto',
                            'reservas.almacen',
                            'reservas.local',
                            'reservas.vendedor',
                        ]),
                ]);
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SIGUIENTE NÚMERO KARDEX
    |--------------------------------------------------------------------------
    */

    private function siguienteNumeroKardex(): int
    {
        $ultimo =
            KardexCab::lockForUpdate()
                ->orderByDesc('numero')
                ->value('numero');

        return ((int) $ultimo) + 1;
    }
}