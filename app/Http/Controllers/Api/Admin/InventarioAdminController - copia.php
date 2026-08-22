<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventarioAdminController extends Controller
{
    /**
     * GET /api/admin/inventarios
     * Listado de existencias por almacén, con el formato que espera
     * InventarioView.vue (código, nombre, almacén, stock, mínimo, estado).
     */
    public function index(Request $request): JsonResponse
    {
        $items = Inventario::with(['producto', 'almacen'])
            ->when($request->filled('almacen_id'), fn ($q) => $q->where('almacen_id', $request->input('almacen_id')))
            ->when($request->filled('q'), function ($query) use ($request) {
                $texto = $request->input('q');
                $query->whereHas('producto', function ($q) use ($texto) {
                    $q->where('codigo', 'like', "%{$texto}%")
                        ->orWhere('descripcion', 'like', "%{$texto}%");
                });
            })
            ->get()
            ->map(fn (Inventario $inv) => $this->formato($inv));

        return response()->json(['data' => $items]);
    }

    /**
     * POST /api/admin/inventarios/movimiento
     * Agrega o quita cantidad de un producto en un almacén.
     * Body: { producto_id, almacen_id, local_id?, tipo: 'entrada'|'salida', cantidad, motivo? }
     */
  /*  public function registrarMovimiento(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'producto_id' => ['required', 'exists:productos,id'],
            'almacen_id' => ['required', 'exists:almacenes,id'],
            'local_id' => ['nullable', 'exists:locales,id'],
            'tipo' => ['required', 'in:entrada,salida,ajuste'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        $inventario = DB::transaction(function () use ($datos) {
            $inventario = Inventario::firstOrCreate(
                [
                    'producto_id' => $datos['producto_id'],
                    'almacen_id' => $datos['almacen_id'],
                    'local_id' => $datos['local_id'] ?? null,
                ],
                ['usuario_id' => Auth::id(), 'cantidad' => 0, 'cantidad_reservada' => 0]
            );

            if ($datos['tipo'] === 'entrada') {
                $inventario->increment('cantidad', $datos['cantidad']);
            } elseif ($datos['tipo'] === 'salida') {
                if ($inventario->cantidad < $datos['cantidad']) {
                    throw new \Exception('No hay suficiente stock para quitar esa cantidad.');
                }
                $inventario->decrement('cantidad', $datos['cantidad']);
            } else {
                // ajuste: fija la cantidad exacta en vez de sumar/restar
                $inventario->update(['cantidad' => $datos['cantidad']]);
            }

            $inventario->update(['usuario_id' => Auth::id()]);

            return $inventario;
        });

        return response()->json([
            'data' => $this->formato($inventario->fresh(['producto', 'almacen'])),
        ]);
    }*/

    public function registrarMovimiento(Request $request)
{
    $datos = $request->validate([
        'id_almacen' => [
            'required',
            'exists:almacenes,id'
        ],

        'tipo_transaccion' => [
            'required',
            Rule::in([
                'ingreso',
                'egreso'
            ])
        ],

        'observacion' => [
            'nullable',
            'string',
            'max:255'
        ],

        'productos' => [
            'required',
            'array',
            'min:1'
        ],

        'productos.*.id_producto' => [
            'required',
            'exists:productos,id'
        ],

        'productos.*.cantidad' => [
            'required',
            'numeric',
            'min:0.01'
        ],
    ]);

    return DB::transaction(function () use ($datos) {

        // Crear cabecera

        $kardex = KardexCab::create([
            'id_almacen' => $datos['id_almacen'],
            'fecha' => now(),
            'tipo_transaccion' => $datos['tipo_transaccion'],
            'id_usuario' => Auth::id(),
            'activo' => true,
            'observacion' => $datos['observacion'] ?? null,
        ]);

        // Procesar productos

        foreach ($datos['productos'] as $item) {

            // Buscar inventario del almacén

            $inventario = Inventario::where(
                    'almacen_id',
                    $datos['id_almacen']
                )
                ->where(
                    'producto_id',
                    $item['id_producto']
                )
                ->lockForUpdate()
                ->first();

            // Para ingreso, crearlo si no existe

            if (
                ! $inventario &&
                $datos['tipo_transaccion'] === 'ingreso'
            ) {
                $inventario = Inventario::create([
                    'producto_id' => $item['id_producto'],
                    'almacen_id' => $datos['id_almacen'],
                    'cantidad' => 0,
                    'cantidad_reservada' => 0,
                    'usuario_id' => Auth::id(),
                ]);
            }

            // Para egreso debe existir

            if (! $inventario) {
                throw ValidationException::withMessages([
                    'productos' =>
                        "El producto {$item['id_producto']} no existe en este almacén."
                ]);
            }

            // EGRESO

            if (
                $datos['tipo_transaccion'] === 'egreso'
            ) {

                $disponible =
                    $inventario->cantidad -
                    $inventario->cantidad_reservada;

                if (
                    $disponible < $item['cantidad']
                ) {
                    throw ValidationException::withMessages([
                        'productos' =>
                            "Stock insuficiente para el producto {$item['id_producto']}."
                    ]);
                }

                $inventario->decrement(
                    'cantidad',
                    $item['cantidad']
                );
            }

            // INGRESO

            else {

                $inventario->increment(
                    'cantidad',
                    $item['cantidad']
                );
            }

            // Crear detalle

            $kardex->detalles()->create([
                'id_producto' => $item['id_producto'],
                'cantidad' => $item['cantidad'],
            ]);

        }

        return response()->json([
            'message' =>
                'Movimiento registrado correctamente.',
            'data' =>
                $kardex->load([
                    'almacen',
                    'usuario',
                    'detalles.producto'
                ])
        ], 201);
    });
}

    private function formato(Inventario $inv): array
    {
        return [
            'id' => $inv->id,
            'producto_id' => $inv->producto_id,
            'codigo' => $inv->producto?->codigo,
            'nombre' => $inv->producto?->descripcion,
            'almacen' => $inv->almacen ? ['id' => $inv->almacen->id, 'nombre' => $inv->almacen->nombre] : null,
            'stock_disponible' => $inv->cantidad - $inv->cantidad_reservada,
            'cantidad' => $inv->cantidad,
            'cantidad_reservada' => $inv->cantidad_reservada,
            'stock_minimo' => $inv->producto?->stock_minimo ?? 0,
        ];
    }
}
