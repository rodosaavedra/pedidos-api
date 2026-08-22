<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePedidoRequest;
use App\Http\Resources\PedidoResource;
use App\Models\Pedido;
use App\Models\Producto;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PedidoController extends Controller
{
    /**
     * Recepciona un pedido enviado desde pedidos-app.
     *
     * IMPORTANTE:
     * Este controlador solamente registra el pedido.
     *
     * NO:
     * - descuenta stock
     * - crea reservas
     * - asigna almacén
     * - asigna local
     * - asigna vendedor
     * - modifica inventarios
     * - genera Kardex
     *
     * La asignación del inventario y la reserva se realizará
     * posteriormente desde pedidos-admin.
     */
    public function store(StorePedidoRequest $request): PedidoResource
    {
        $datos = $request->validated();

        $pedido = DB::transaction(function () use ($datos) {

            /*
            |--------------------------------------------------------------------------
            | OBTENER PRODUCTOS
            |--------------------------------------------------------------------------
            |
            | El precio y los datos del producto siempre se obtienen
            | desde la base de datos.
            |
            */

            $productoIds = collect($datos['productos'])
                ->pluck('producto_id')
                ->unique();

            $productos = Producto::whereIn('id', $productoIds)
                ->get()
                ->keyBy('id');


            $total = 0;

            $detalles = [];


            /*
            |--------------------------------------------------------------------------
            | PROCESAR PRODUCTOS DEL PEDIDO
            |--------------------------------------------------------------------------
            */

            foreach ($datos['productos'] as $item) {

                $producto = $productos->get(
                    $item['producto_id']
                );


                /*
                |--------------------------------------------------------------------------
                | VALIDAR PRODUCTO
                |--------------------------------------------------------------------------
                */

                if (!$producto || !$producto->activo) {

                    throw ValidationException::withMessages([
                        'productos' => [
                            "El producto {$item['producto_id']} ya no está disponible."
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | CANTIDAD
                |--------------------------------------------------------------------------
                |
                | Aquí solamente validamos que la cantidad enviada
                | sea válida.
                |
                | NO consultamos ni modificamos inventario.
                |
                */

                $cantidad = (int) $item['cantidad'];

                if ($cantidad <= 0) {

                    throw ValidationException::withMessages([
                        'productos' => [
                            "La cantidad del producto {$producto->descripcion} debe ser mayor a cero."
                        ],
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | PRECIO REAL DESDE BD
                |--------------------------------------------------------------------------
                */

                $precioUnitario =
                    (float) $producto->precio;


                $subtotal =
                    $precioUnitario * $cantidad;


                $total += $subtotal;


                /*
                |--------------------------------------------------------------------------
                | GUARDAR SNAPSHOT DEL PRODUCTO
                |--------------------------------------------------------------------------
                */

                $detalles[] = [

                    'producto_id' =>
                        $producto->id,

                    'codigo_producto' =>
                        $producto->codigo,

                    'descripcion_producto' =>
                        $producto->descripcion,

                    'cantidad' =>
                        $cantidad,

                    'precio_unitario' =>
                        $precioUnitario,

                    'subtotal' =>
                        $subtotal,
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | CREAR PEDIDO
            |--------------------------------------------------------------------------
            |
            | En este momento el pedido solamente queda registrado.
            |
            */

            $pedido = Pedido::create([

                'nombre_cliente' =>
                    $datos['nombre_cliente'],

                'celular_whatsapp' =>
                    $datos['celular_whatsapp'],

                'direccion_entrega' =>
                    $datos['direccion_entrega'],

                'observaciones' =>
                    $datos['observaciones'] ?? null,

                'estado' =>
                    'pendiente',

                'total' =>
                    $total,

                'fecha_pedido' =>
                    now(),
            ]);


            /*
            |--------------------------------------------------------------------------
            | CREAR DETALLES
            |--------------------------------------------------------------------------
            */

            $pedido->detalles()
                ->createMany($detalles);


            return $pedido;
        });


        /*
        |--------------------------------------------------------------------------
        | RESPUESTA
        |--------------------------------------------------------------------------
        */

        return new PedidoResource(
            $pedido->load('detalles')
        );
    }


    /**
     * Muestra un pedido puntual.
     *
     * Este método tampoco modifica inventario.
     */
    public function show(Pedido $pedido): PedidoResource
    {
        return new PedidoResource(
            $pedido->load('detalles')
        );
    }
}