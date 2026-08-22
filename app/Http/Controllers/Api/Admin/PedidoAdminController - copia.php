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
            'reservas.inventario.producto',
            'reservas.almacen',
            'reservas.local',
            'reservas.vendedor',
        ]);

        /*
        |--------------------------------------------------------------------------
        | FILTRO POR ESTADO
        |--------------------------------------------------------------------------
        */

        if ($request->filled('estado')) {
            $query->where(
                'estado',
                $request->estado
            );
        }

        /*
        |--------------------------------------------------------------------------
        | BUSCAR CLIENTE / CELULAR
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | FECHAS
        |--------------------------------------------------------------------------
        */

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

            'reservas.inventario.producto',

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
    | CONFIRMAR PEDIDO
    |--------------------------------------------------------------------------
    |
    | IMPORTANTE:
    |
    | Aquí NO se descuenta cantidad.
    |
    | Solamente se aumenta:
    |
    | inventarios.cantidad_reservada
    |
    */
    public function actualizarEstado(Request $request, Pedido $pedido): JsonResponse
    {
        $estadoActual = $pedido->estado;
     
        $reglas = [
            'estado' => ['required', 'in:pendiente,confirmado,preparando,listo,entregado,cancelado'],
        ];
     
        // Solo exige almacén/vendedor si de verdad se está confirmando por primera vez
        if ($request->input('estado') === 'confirmado' && $estadoActual === 'pendiente') {
            $reglas['almacen_id'] = ['required', 'exists:almacenes,id'];
            $reglas['vendedor_id'] = ['required', 'exists:vendedores,id'];
        }
     
        $datos = $request->validate($reglas);
     
        // Nunca se puede volver a "confirmado" si ya se salió de "pendiente"
        if ($datos['estado'] === 'confirmado' && $estadoActual !== 'pendiente') {
            throw ValidationException::withMessages([
                'estado' => 'Este pedido ya fue confirmado antes; no se puede confirmar de nuevo.',
            ]);
        }
     
        DB::transaction(function () use ($datos, $pedido, $estadoActual) {
     
            $pedido->update(['estado' => $datos['estado']]);
     
            // Solo la PRIMERA vez que pasa a confirmado se crea la reserva
            // y se fija el almacén/vendedor en el propio pedido
            if ($datos['estado'] === 'confirmado' && $estadoActual === 'pendiente') {
     
                $pedido->update([
                    'almacen_id' => $datos['almacen_id'],
                    'vendedor_id' => $datos['vendedor_id'],
                ]);
     
                $cantidadTotal = $pedido->detalles()->sum('cantidad');
     
                PedidoReserva::create([
                    'pedido_id' => $pedido->id,
                    'almacen_id' => $datos['almacen_id'],
                    'vendedor_id' => $datos['vendedor_id'],
                    'cantidad' => $cantidadTotal,
                    'estado' => 'activa',
                ]);
            }
        });
     
        return response()->json(['data' => $pedido->fresh(['detalles'])]);
    }

    public function confirmar(Request $request, int $id): JsonResponse {

        $datos = $request->validate([

            'reservas' => [
                'required',
                'array',
                'min:1',
            ],

            'reservas.*.detalle_id' => [
                'required',
                'integer',
            ],

            'reservas.*.inventario_id' => [
                'required',
                'integer',
                'exists:inventarios,id',
            ],

            'reservas.*.cantidad' => [
                'required',
                'numeric',
                'gt:0',
            ],
        ]);

        return DB::transaction(function () use (
            $id,
            $datos
        ) {

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
            | VALIDAR ESTADO
            |--------------------------------------------------------------------------
            */

            if ($pedido->estado !== 'PENDIENTE') {

                throw ValidationException::withMessages([
                    'estado' => [
                        'Solo se pueden confirmar pedidos pendientes.'
                    ],
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | CONTROLAR QUE NO HAYA DETALLES REPETIDOS
            |--------------------------------------------------------------------------
            */

            $detalleIds = collect($datos['reservas'])
                ->pluck('detalle_id');

            if ($detalleIds->duplicates()->isNotEmpty()) {

                throw ValidationException::withMessages([
                    'reservas' => [
                        'No puede existir más de una reserva para el mismo detalle.'
                    ],
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | VALIDAR QUE TODOS LOS DETALLES DEL PEDIDO
            | TENGAN RESERVA
            |--------------------------------------------------------------------------
            */

            $idsDetallesPedido = $pedido->detalles
                ->pluck('id')
                ->sort()
                ->values();

            $idsDetallesReserva = $detalleIds
                ->sort()
                ->values();

            if (
                $idsDetallesPedido->count()
                !==
                $idsDetallesReserva->count()
                ||
                $idsDetallesPedido->diff($idsDetallesReserva)->isNotEmpty()
                ||
                $idsDetallesReserva->diff($idsDetallesPedido)->isNotEmpty()
            ) {

                throw ValidationException::withMessages([
                    'reservas' => [
                        'Debe asignarse un inventario a cada detalle del pedido.'
                    ],
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | PROCESAR CADA RESERVA
            |--------------------------------------------------------------------------
            */

            foreach ($datos['reservas'] as $reservaData) {

                /*
                |--------------------------------------------------------------------------
                | BUSCAR DETALLE DEL PEDIDO
                |--------------------------------------------------------------------------
                */

                $detalle = $pedido->detalles
                    ->firstWhere(
                        'id',
                        $reservaData['detalle_id']
                    );

                if (!$detalle) {

                    throw ValidationException::withMessages([
                        'reservas' => [
                            'El detalle indicado no pertenece al pedido.'
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | VALIDAR CANTIDAD
                |--------------------------------------------------------------------------
                */

                $cantidadSolicitada =
                    (float) $detalle->cantidad;

                $cantidadReserva =
                    (float) $reservaData['cantidad'];

                if (
                    abs(
                        $cantidadSolicitada -
                        $cantidadReserva
                    ) > 0.0001
                ) {

                    throw ValidationException::withMessages([
                        'reservas' => [
                            "La cantidad reservada para {$detalle->descripcion_producto} debe ser {$cantidadSolicitada}."
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | BLOQUEAR INVENTARIO
                |--------------------------------------------------------------------------
                */

                $inventario = Inventario::with([
                    'producto',
                    'almacen',
                    'local',
                    'vendedor',
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
                            "El inventario seleccionado no corresponde al producto {$detalle->descripcion_producto}."
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | STOCK DISPONIBLE
                |--------------------------------------------------------------------------
                */

                $cantidadDisponible =
                    (float) $inventario->cantidad
                    -
                    (float) $inventario->cantidad_reservada;


                if (
                    $cantidadReserva >
                    $cantidadDisponible
                ) {

                    throw ValidationException::withMessages([
                        'inventario' => [
                            "Stock insuficiente para {$detalle->descripcion_producto}. Disponible: {$cantidadDisponible}."
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | CREAR RESERVA
                |--------------------------------------------------------------------------
                |
                | Guardamos también la ubicación como snapshot.
                |
                */

                PedidoReserva::create([

                    'pedido_id' =>
                        $pedido->id,

                    'inventario_id' =>
                        $inventario->id,

                    'almacen_id' =>
                        $inventario->almacen_id,

                    'local_id' =>
                        $inventario->local_id,

                    'vendedor_id' =>
                        $inventario->vendedor_id,

                    'cantidad' =>
                        $cantidadReserva,

                    'estado' =>
                        'RESERVADA',
                ]);


                /*
                |--------------------------------------------------------------------------
                | AUMENTAR RESERVA
                |--------------------------------------------------------------------------
                */

                $inventario->increment(
                    'cantidad_reservada',
                    $cantidadReserva
                );
            }


            /*
            |--------------------------------------------------------------------------
            | CAMBIAR ESTADO DEL PEDIDO
            |--------------------------------------------------------------------------
            */

            $pedido->estado = 'CONFIRMADO';

            $pedido->save();


            /*
            |--------------------------------------------------------------------------
            | RESPUESTA
            |--------------------------------------------------------------------------
            */

            $pedido->load([
                'detalles.producto',

                'reservas.inventario.producto',

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
        });
    }


    /*
    |--------------------------------------------------------------------------
    | CANCELAR PEDIDO
    |--------------------------------------------------------------------------
    |
    | PENDIENTE:
    | simplemente cambia a CANCELADO.
    |
    | CONFIRMADO:
    | libera cantidad_reservada.
    |
    */

    public function cancelar(Request $request, int $id): JsonResponse {

        $datos = $request->validate([

            'motivo' => [
                'nullable',
                'string',
                'max:255',
            ],

        ]);


        return DB::transaction(function () use (
            $id,
            $datos
        ) {

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


            /*
            |--------------------------------------------------------------------------
            | PEDIDO YA TERMINADO
            |--------------------------------------------------------------------------
            */

            if (
                in_array(
                    $pedido->estado,
                    [
                        'ENTREGADO',
                        'CANCELADO'
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

            if (
                $pedido->estado === 'CONFIRMADO'
            ) {

                foreach (
                    $pedido->reservas
                    as $reserva
                ) {

                    if (
                        $reserva->estado
                        !==
                        'RESERVADA'
                    ) {
                        continue;
                    }


                    $inventario =
                        Inventario::lockForUpdate()
                            ->find(
                                $reserva->inventario_id
                            );


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
                        $inventario->cantidad_reservada
                        <
                        $reserva->cantidad
                    ) {

                        throw ValidationException::withMessages([
                            'inventario' => [
                                'La cantidad reservada del inventario es inconsistente.'
                            ],
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | LIBERAR RESERVA
                    |--------------------------------------------------------------------------
                    */

                    $inventario->decrement(
                        'cantidad_reservada',
                        $reserva->cantidad
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | ACTUALIZAR RESERVA
                    |--------------------------------------------------------------------------
                    */

                    $reserva->estado =
                        'LIBERADA';

                    $reserva->save();
                }
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
            | ESTADO
            |--------------------------------------------------------------------------
            */

            $pedido->estado =
                'CANCELADO';

            $pedido->save();


            return response()->json([
                'success' => true,
                'message' =>
                    'Pedido cancelado correctamente.',
                'data' =>
                    $pedido->fresh([
                        'detalles.producto',
                        'reservas.inventario.producto',
                        'reservas.almacen',
                        'reservas.local',
                        'reservas.vendedor',
                    ]),
            ]);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | ENTREGAR PEDIDO
    |--------------------------------------------------------------------------
    |
    | AQUÍ recién se descuenta el stock.
    |
    | cantidad:
    |     100 -> 90
    |
    | cantidad_reservada:
    |      10 -> 0
    |
    | Y SE CREA EL KARDEX DE EGRESO.
    |
    */

    public function entregar(Request $request, int $id): JsonResponse {

        return DB::transaction(function () use ($id) {

            /*
            |--------------------------------------------------------------------------
            | USUARIO
            |--------------------------------------------------------------------------
            */

            $usuarioId =
                auth()->id();


            /*
            |--------------------------------------------------------------------------
            | BLOQUEAR PEDIDO
            |--------------------------------------------------------------------------
            */

            $pedido = Pedido::with([
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
                $pedido->estado !==
                'CONFIRMADO'
            ) {

                throw ValidationException::withMessages([
                    'estado' => [
                        'Solo se pueden entregar pedidos confirmados.'
                    ],
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | CREAR CABECERA KARDEX
            |--------------------------------------------------------------------------
            |
            | Un pedido puede utilizar varios inventarios,
            | almacenes o vendedores.
            |
            | Por eso el Kardex también conserva la ubicación
            | de cada detalle.
            |
            */

            $kardex = null;


            foreach (
                $pedido->reservas
                as $reserva
            ) {

                if (
                    $reserva->estado
                    !==
                    'RESERVADA'
                ) {
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | BLOQUEAR INVENTARIO
                |--------------------------------------------------------------------------
                */

                $inventario =
                    Inventario::with([
                        'producto',
                    ])
                    ->lockForUpdate()
                    ->find(
                        $reserva->inventario_id
                    );


                if (!$inventario) {

                    throw ValidationException::withMessages([
                        'inventario' => [
                            'No se encontró el inventario de la reserva.'
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | VALIDAR STOCK
                |--------------------------------------------------------------------------
                */

                if (
                    $inventario->cantidad
                    <
                    $reserva->cantidad
                ) {

                    throw ValidationException::withMessages([
                        'inventario' => [
                            "No existe stock suficiente para entregar {$inventario->producto->descripcion}."
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | VALIDAR RESERVA
                |--------------------------------------------------------------------------
                */

                if (
                    $inventario->cantidad_reservada
                    <
                    $reserva->cantidad
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
                | CREAR KARDEX CABECERA
                |--------------------------------------------------------------------------
                |
                | Se crea una cabecera por cada combinación
                | de almacén/local/vendedor.
                |
                */

                if (!$kardex) {

                    $kardex = KardexCab::create([

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


                /*
                |--------------------------------------------------------------------------
                | CREAR DETALLE KARDEX
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
                | MARCAR RESERVA COMO UTILIZADA
                |--------------------------------------------------------------------------
                */

                $reserva->estado =
                    'CONFIRMADA';

                $reserva->save();
            }


            /*
            |--------------------------------------------------------------------------
            | CAMBIAR PEDIDO A ENTREGADO
            |--------------------------------------------------------------------------
            */

            $pedido->estado =
                'ENTREGADO';

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

                        'reservas.inventario.producto',

                        'reservas.almacen',

                        'reservas.local',

                        'reservas.vendedor',
                    ]),
            ]);
        });
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