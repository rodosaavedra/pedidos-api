<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductoController extends Controller
{
    /**
     * Catálogo de productos para la grilla de la app.
     * Filtros opcionales: ?categoria_id=&marca_id=&buscar=
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $productos = Producto::with(['categoria', 'marca'])
            ->where('activo', true)
            ->when($request->filled('categoria_id'), function ($query) use ($request) {
                $query->where('categoria_id', $request->input('categoria_id'));
            })
            ->when($request->filled('marca_id'), function ($query) use ($request) {
                $query->where('marca_id', $request->input('marca_id'));
            })
            ->when($request->filled('buscar'), function ($query) use ($request) {
                $texto = $request->input('buscar');
                $query->where(function ($q) use ($texto) {
                    $q->where('codigo', 'like', "%{$texto}%")
                        ->orWhere('descripcion', 'like', "%{$texto}%");
                });
            })
            ->orderBy('descripcion')
            ->paginate(20);

        return ProductoResource::collection($productos);
    }
}