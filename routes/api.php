<?php

use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\MarcaController;
use App\Http\Controllers\Api\PedidoController;
use App\Http\Controllers\Api\ProductoController;
use Illuminate\Support\Facades\Route;

// Catálogo (lectura pública, la app la consume sin login)
Route::get('/categorias', [CategoriaController::class, 'index']);
Route::get('/marcas', [MarcaController::class, 'index']);
Route::get('/productos', [ProductoController::class, 'index']);

// Pedidos
Route::post('/pedidos', [PedidoController::class, 'store']);
Route::get('/pedidos/{pedido}', [PedidoController::class, 'show']);