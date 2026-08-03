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
     * Crea un pedido a partir del carrito enviado por la app.
     * El precio y la validación de stock SIEMPRE se toman de la base
     * de datos, nunca del valor que mande el frontend.
     */
    public function store(StorePedidoRequest $request): PedidoResource
    {
        $datos = $request->validated();

        $pedido = DB::transaction(function () use ($datos) {
            // Bloquea las filas de los productos involucrados para evitar
            // que dos pedidos simultáneos vendan el mismo stock
            $productoIds = collect($datos['productos'])->pluck('producto_id');
            $productos = Producto::whereIn('id', $productoIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $total = 0;
            $detalles = [];

            foreach ($datos['productos'] as $item) {
                $producto = $productos->get($item['producto_id']);

                if (! $producto || ! $producto->activo) {
                    throw ValidationException::withMessages([
                        'productos' => "El producto {$item['producto_id']} ya no está disponible.",
                    ]);
                }

                if ($producto->stock < $item['cantidad']) {
                    throw ValidationException::withMessages([
                        'productos' => "Stock insuficiente para {$producto->descripcion} (disponible: {$producto->stock}).",
                    ]);
                }

                $subtotal = $producto->precio * $item['cantidad'];
                $total += $subtotal;

                $detalles[] = [
                    'producto_id' => $producto->id,
                    'codigo_producto' => $producto->codigo,
                    'descripcion_producto' => $producto->descripcion,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $producto->precio,
                    'subtotal' => $subtotal,
                ];

                // Descuenta el stock reservado por este pedido
                $producto->decrement('stock', $item['cantidad']);
            }

            $pedido = Pedido::create([
                'nombre_cliente' => $datos['nombre_cliente'],
                'celular_whatsapp' => $datos['celular_whatsapp'],
                'direccion_entrega' => $datos['direccion_entrega'],
                'observaciones' => $datos['observaciones'] ?? null,
                'estado' => 'pendiente',
                'total' => $total,
                'fecha_pedido' => now(),
            ]);

            $pedido->detalles()->createMany($detalles);

            return $pedido;
        });

        return new PedidoResource($pedido->load('detalles'));
    }

    /**
     * Muestra un pedido puntual con su detalle (para pantalla de confirmación
     * o seguimiento del pedido).
     */
    public function show(Pedido $pedido): PedidoResource
    {
        return new PedidoResource($pedido->load('detalles'));
    }
}