<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'descripcion' => $this->descripcion,
            'precio' => (float) $this->precio,
            //'stock' => (float) ($this->stock_total ?? 0),
            'stock' => (int) ($this->stock_total ?? 0),
            'categoria' => $this->whenLoaded('categoria', fn () => [
                'id' => $this->categoria->id,
                'nombre' => $this->categoria->nombre,
            ]),
            'marca' => $this->whenLoaded('marca', fn () => $this->marca ? [
                'id' => $this->marca->id,
                'nombre' => $this->marca->nombre,
            ] : null),
        ];
    }
}