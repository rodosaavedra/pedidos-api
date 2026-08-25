<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pos\ProductoPos;

class PosCatalogoController extends Controller
{
    public function productos()
    {
        $productos = ProductoPos::query()
            ->where('estado', 1)
            ->orderBy('descripcion')
            ->get();

        return response()->json([
            'data' => $productos,
        ]);
    }

    public function almacen()
    {
        $almacen = Almacen::query()
            ->where('estado', 1)
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'data' => $almacen,
        ]);
    }

     public function cliente()
    {
        $clientes = Cliente::query()
            ->where('estado', 1)
            ->orderBy('nombres')
            ->get();

        return response()->json([
            'data' => $clientes,
        ]);
    }

     public function local()
    {
        $local = Local::query()
            ->where('estado', 1)
            ->orderBy('descripcion')
            ->get();

        return response()->json([
            'data' => $productos,
        ]);
    }
     public function vendedores()
    {
        $productos = Producto::query()
            ->where('estado', 1)
            ->orderBy('descripcion')
            ->get();

        return response()->json([
            'data' => $productos,
        ]);
    }
     public function categorias()
    {
        $productos = Producto::query()
            ->where('estado', 1)
            ->orderBy('descripcion')
            ->get();

        return response()->json([
            'data' => $productos,
        ]);
    } 
     public function proveedores()
    {
        $productos = Producto::query()
            ->where('estado', 1)
            ->orderBy('descripcion')
            ->get();

        return response()->json([
            'data' => $productos,
        ]);
    }

}
