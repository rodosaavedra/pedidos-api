<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Models\Inventario;

class ProductoController extends Controller
{
    /**
     * Catálogo público de productos.
     *
     * Stock disponible real:
     *
     * SUM(cantidad - cantidad_reservada)
     *
     * considerando todos los almacenes.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $productos = Producto::with([
                'categoria',
                'marca',
            ])

            // STOCK DISPONIBLE REAL
            // cantidad total - cantidad reservada
            ->addSelect([
                'stock_total' => Inventario::selectRaw(
                    'COALESCE(SUM(cantidad - cantidad_reservada), 0)'
                )
                ->whereColumn(
                    'inventarios.producto_id',
                    'productos.id'
                )
            ])

            ->where('activo', true)

            // FILTRO CATEGORÍA
            ->when(
                $request->filled('categoria_id'),
                function ($query) use ($request) {
                    $query->where(
                        'categoria_id',
                        $request->input('categoria_id')
                    );
                }
            )

            // FILTRO MARCA
            ->when(
                $request->filled('marca_id'),
                function ($query) use ($request) {
                    $query->where(
                        'marca_id',
                        $request->input('marca_id')
                    );
                }
            )

            // BUSCAR
            ->when(
                $request->filled('buscar'),
                function ($query) use ($request) {

                    $texto = trim(
                        $request->input('buscar')
                    );

                    $query->where(function ($q) use ($texto) {

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
                    });
                }
            )

            ->orderBy('descripcion')
            ->paginate(20);

        return ProductoResource::collection($productos);
    }
}