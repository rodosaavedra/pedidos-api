<?php

use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\MarcaController;
use App\Http\Controllers\Api\PedidoController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\Admin\AuthController;
use App\Http\Controllers\Api\Admin\ProductoAdminController;
use App\Http\Controllers\Api\Admin\PedidoAdminController;
use App\Http\Controllers\Api\Admin\InventarioAdminController;
use App\Http\Controllers\Api\Admin\KardexAdminController;
use App\Http\Controllers\Api\Admin\AlmacenAdminController;
use App\Http\Controllers\Api\Admin\LocalAdminController;
use App\Http\Controllers\Api\Admin\VendedorAdminController;

use App\Http\Controllers\Api\PosConexionController;
use App\Http\Controllers\Api\PosCatalogoController;
use App\Http\Controllers\Api\Pos\PosProductoController;
use App\Http\Controllers\Api\Pos\PosClienteController;
use App\Http\Controllers\Api\Pos\PosAlmacenController;
use App\Http\Controllers\Api\Pos\PosVendedorController;
use App\Http\Controllers\Api\Pos\PosSyncController;

use Illuminate\Support\Facades\Route;


// =====================================================
// CATÁLOGO PÚBLICO
// =====================================================

Route::get(
    '/Pos/probar',
    [PosConexionController::class, 'probar']
);
Route::get(
    '/Pos/productos',
    [PosCatalogoController::class, 'productos']
);

Route::get(
    '/categorias',
    [CategoriaController::class, 'index']
);

Route::get(
    '/marcas',
    [MarcaController::class, 'index']
);

Route::get(
    '/productos',
    [ProductoController::class, 'index']
);


// =====================================================
// PEDIDOS PÚBLICOS
// =====================================================

Route::post(
    '/pedidos',
    [PedidoController::class, 'store']
);

Route::get(
    '/pedidos/{pedido}',
    [PedidoController::class, 'show']
);


// =====================================================
// LOGIN ADMIN
// =====================================================

Route::post(
    '/admin/login',
    [AuthController::class, 'login']
);


// =====================================================
// ADMINISTRACIÓN PROTEGIDA
// =====================================================

Route::middleware('auth:sanctum')
    ->prefix('admin')
    ->group(function () {

        // ---------------------------------------------
        // AUTENTICACIÓN
        // ---------------------------------------------

        Route::post(
            '/logout',
            [AuthController::class, 'logout']
        );

        Route::get(
            '/me',
            [AuthController::class, 'me']
        );
    
        Route::prefix('pedidos')->group(function () {

            Route::get('/', [
                PedidoAdminController::class,
                'index'
            ]);

            Route::get('/{id}/disponibilidad', [
                PedidoAdminController::class,
                'disponibilidad'
            ]);

            Route::post('/{id}/confirmar', [
                PedidoAdminController::class,
                'confirmar'
            ]);

            Route::post('/{id}/cancelar', [
                PedidoAdminController::class,
                'cancelar'
            ]);

            Route::post('/{id}/entregar', [
                PedidoAdminController::class,
                'entregar'
            ]);

            Route::patch('/{pedido}/estado', [
                PedidoAdminController::class,
                'actualizarEstado'
            ]);

            Route::get('/{pedido}', [
                PedidoAdminController::class,
                'show'
            ]);
        });
        // ---------------------------------------------
        // PRODUCTOS
        // ---------------------------------------------

        Route::apiResource(
            'productos',
            ProductoAdminController::class
        );


        // ---------------------------------------------
        // INVENTARIOS
        // ---------------------------------------------

        Route::prefix('inventarios')->group(function () {
            Route::get('/', [InventarioAdminController::class, 'index']);
            Route::get('/buscar-productos', [InventarioAdminController::class, 'buscarProductos']);
            Route::get('/buscar', [InventarioAdminController::class, 'buscar']);
            Route::post('/ingreso', [InventarioAdminController::class, 'ingreso']);
            Route::post('/egreso', [InventarioAdminController::class, 'egreso']);
            Route::get('/kardex/{id}', [InventarioAdminController::class, 'kardex']);
            Route::post('/kardex/{id}/anular', [InventarioAdminController::class, 'anular']);
            Route::get('/{id}', [InventarioAdminController::class, 'show']);
        });

         
        
        // ---------------------------------------------
        // KARDEX
        // ---------------------------------------------

        Route::get(
            '/kardex',
            [KardexAdminController::class, 'index']
        );

        Route::get(
            '/kardex/{kardex}',
            [KardexAdminController::class, 'show']
        );
        // =====================================================
        // ALMACENES
        // =====================================================

        Route::apiResource(
            'locales',
            LocalAdminController::class
        );
         Route::apiResource(
            'almacenes',
            AlmacenAdminController::class
        );
          Route::apiResource(
            'vendedores',
            VendedorAdminController::class
        );

    });


        // =====================================================
        // DB_Pos
        // =====================================================

        Route::prefix('Pos')->group(function () {

            // ---------------------------------------------
            // PRODUCTOS
            // ---------------------------------------------

            Route::get(
                '/productos',
                [PosProductoController::class, 'index']
            );

            Route::get(
                '/productos/{id}',
                [PosProductoController::class, 'show']
            );


            // ---------------------------------------------
            // CLIENTES
            // ---------------------------------------------

            Route::get(
                '/clientes',
                [PosClienteController::class, 'index']
            );

            Route::get(
                '/clientes/{id}',
                [PosClienteController::class, 'show']
            );


            // ---------------------------------------------
            // ALMACENES
            // ---------------------------------------------

            Route::get(
                '/almacenes',
                [PosAlmacenController::class, 'index']
            );

            Route::get(
                '/almacenes/{id}',
                [PosAlmacenController::class, 'show']
            );


            // ---------------------------------------------
            // VENDEDORES
            // ---------------------------------------------

            Route::get(
                '/vendedores',
                [PosVendedorController::class, 'index']
            );

            Route::get(
                '/vendedores/{id}',
                [PosVendedorController::class, 'show']
            );


            // ---------------------------------------------
            // SINCRONIZACIÓN
            // ---------------------------------------------

            Route::post(
                '/sync/productos',
                [PosSyncController::class, 'productos']
            );

            Route::post(
                '/sync/inventario',
                [PosSyncController::class, 'inventario']
            );

            Route::post(
                '/sync',
                [PosSyncController::class, 'sync']
            );
    });  
    