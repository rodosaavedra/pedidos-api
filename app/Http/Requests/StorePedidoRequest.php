<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // ajusta si luego agregas autenticación de clientes
    }

    public function rules(): array
    {
        return [
            'nombre_cliente' => ['required', 'string', 'max:150'],
            'celular_whatsapp' => ['required', 'string', 'max:20'],
            'direccion_entrega' => ['required', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string'],

            'productos' => ['required', 'array', 'min:1'],
            'productos.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'productos.*.cantidad' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'productos.required' => 'El carrito no puede estar vacío.',
            'productos.*.producto_id.exists' => 'Uno de los productos del carrito ya no existe.',
            'productos.*.cantidad.min' => 'La cantidad debe ser al menos 1.',
        ];
    }
}