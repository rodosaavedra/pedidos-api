<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PedidoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre_cliente' => $this->nombre_cliente,
            'celular_whatsapp' => $this->celular_whatsapp,
            'direccion_entrega' => $this->direccion_entrega,
            'estado' => $this->estado,
            'total' => (float) $this->total,
            'observaciones' => $this->observaciones,
            'fecha_pedido' => $this->fecha_pedido,
            'detalles' => $this->whenLoaded('detalles', function () {
                return $this->detalles->map(fn ($detalle) => [
                    'producto_id' => $detalle->producto_id,
                    'codigo_producto' => $detalle->codigo_producto,
                    'descripcion_producto' => $detalle->descripcion_producto,
                    'cantidad' => $detalle->cantidad,
                    'precio_unitario' => (float) $detalle->precio_unitario,
                    'subtotal' => (float) $detalle->subtotal,
                ]);
            }),
        ];
    }
}