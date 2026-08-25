<?php

namespace App\Http\Controllers\Api\Pos;

use App\Http\Controllers\Controller;
use App\Models\Pos\ProductoPos;
use App\Models\Pos\InventarioPos;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Throwable;

class PosSyncController extends Controller
{
    /**
     * Sincronización general.
     */
    public function sync(): JsonResponse
    {
        try {

            $productos = $this->sincronizarProductosInterno();

            $inventario = $this->sincronizarInventarioInterno();

            return response()->json([
                'ok' => true,
                'mensaje' => 'Sincronización completada correctamente',
                'productos' => $productos,
                'inventario' => $inventario,
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'ok' => false,
                'mensaje' => 'Error durante la sincronización',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Sincronizar únicamente productos.
     */
    public function productos(): JsonResponse
    {
        try {

            $resultado = $this->sincronizarProductosInterno();

            return response()->json([
                'ok' => true,
                'mensaje' => 'Productos sincronizados correctamente',
                'resultado' => $resultado,
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'ok' => false,
                'mensaje' => 'Error al sincronizar productos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Sincronizar únicamente inventario.
     */
    public function inventario(): JsonResponse
    {
        try {

            $resultado = $this->sincronizarInventarioInterno();

            return response()->json([
                'ok' => true,
                'mensaje' => 'Inventario sincronizado correctamente',
                'resultado' => $resultado,
            ]);

        } catch (Throwable $e) {

            return response()->json([
                'ok' => false,
                'mensaje' => 'Error al sincronizar inventario',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * =====================================================
     * PRODUCTOS
     * =====================================================
     */
    private function sincronizarProductosInterno(): array
    {
        $creados = 0;
        $actualizados = 0;

        ProductoPos::query()
            ->orderBy('id')
            ->chunkById(200, function ($productos) use (
                &$creados,
                &$actualizados
            ) {

                foreach ($productos as $productoPos) {

                    /*
                     * IMPORTANTE:
                     *
                     * Aquí usamos el ID de Pos como
                     * referencia externa.
                     *
                     * No debemos perder la relación:
                     *
                     * pedidos-admin.producto
                     *        ↕
                     * db_Pos.productos
                     */

                    $producto = DB::table('productos')
                        ->where('Pos_id', $productoPos->id)
                        ->first();

                    $datos = [
                        'Pos_id' => $productoPos->id,

                        'codigo' => $productoPos->codigo,

                        'nombre' => $productoPos->descripcion,

                        'descripcion' => $productoPos->descripcion,

                        'estado' => (int) $productoPos->estado,

                        'updated_at' => now(),
                    ];

                    if ($producto) {

                        DB::table('productos')
                            ->where('id', $producto->id)
                            ->update($datos);

                        $actualizados++;

                    } else {

                        $datos['created_at'] = now();

                        DB::table('productos')
                            ->insert($datos);

                        $creados++;
                    }
                }
            });

        return [
            'creados' => $creados,
            'actualizados' => $actualizados,
        ];
    }


    /**
     * =====================================================
     * INVENTARIO
     * =====================================================
     */
    private function sincronizarInventarioInterno(): array
    {
        $creados = 0;
        $actualizados = 0;

        InventarioPos::query()
            ->where('estado', 1)
            ->orderBy('id')
            ->chunkById(200, function ($items) use (
                &$creados,
                &$actualizados
            ) {

                foreach ($items as $item) {

                    /*
                     * Buscamos el producto local mediante
                     * el ID original de Pos.
                     */

                    $producto = DB::table('productos')
                        ->where('Pos_id', $item->id_producto)
                        ->first();

                    if (!$producto) {

                        /*
                         * Si el producto todavía no fue
                         * sincronizado, no creamos un
                         * inventario huérfano.
                         */

                        continue;
                    }


                    /*
                     * Buscamos el almacén local.
                     */

                    $almacen = DB::table('almacenes')
                        ->where('Pos_id', $item->id_almacen)
                        ->first();

                    if (!$almacen) {

                        continue;
                    }


                    /*
                     * Buscamos el inventario local.
                     */

                    $inventario = DB::table('inventarios')
                        ->where('producto_id', $producto->id)
                        ->where('almacen_id', $almacen->id)
                        ->first();


                    if ($inventario) {

                        /*
                         * SOLAMENTE ACTUALIZAMOS CANTIDAD.
                         *
                         * NO TOCAMOS:
                         *
                         * cantidad_reservada
                         */

                        DB::table('inventarios')
                            ->where('id', $inventario->id)
                            ->update([
                                'cantidad' => $item->stock,
                                'updated_at' => now(),
                            ]);

                        $actualizados++;

                    } else {

                        DB::table('inventarios')
                            ->insert([
                                'producto_id' => $producto->id,
                                'almacen_id' => $almacen->id,

                                'local_id' =>
                                    $almacen->local_id ?? null,

                                'vendedor_id' => null,

                                'cantidad' => $item->stock,

                                'cantidad_reservada' => 0,

                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);

                        $creados++;
                    }
                }
            });

        return [
            'creados' => $creados,
            'actualizados' => $actualizados,
        ];
    }
}