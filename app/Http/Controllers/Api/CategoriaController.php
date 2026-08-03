<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class CategoriaController extends Controller
{
    public function index(): JsonResponse
    {
        $categorias = Categoria::where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'descripcion', 'imagen'])
            ->map(fn ($categoria) => [
                'id' => $categoria->id,
                'nombre' => $categoria->nombre,
                'descripcion' => $categoria->descripcion,
                'imagen_url' => $this->resolverUrlImagen($categoria->imagen),
            ]);

        return response()->json([
            'data' => $categorias,
        ]);
    }

    /**
     * Si 'imagen' ya es una URL completa (http/https), la usa tal cual.
     * Si es una ruta relativa, la resuelve contra storage/app/public.
     */
    private function resolverUrlImagen(?string $imagen): ?string
    {
        if (! $imagen) {
            return null;
        }

        return str_starts_with($imagen, 'http')
            ? $imagen
            : Storage::url($imagen);
    }
}