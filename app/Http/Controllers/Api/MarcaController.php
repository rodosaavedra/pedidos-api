<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Marca;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MarcaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $marcas = Marca::where('activo', true)
            ->when($request->filled('proveedor_id'), function ($query) use ($request) {
                $query->where('proveedor_id', $request->input('proveedor_id'));
            })
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'imagen', 'proveedor_id'])
            ->map(fn ($marca) => [
                'id' => $marca->id,
                'nombre' => $marca->nombre,
                'proveedor_id' => $marca->proveedor_id,
                'imagen_url' => $this->resolverUrlImagen($marca->imagen),
            ]);

        return response()->json([
            'data' => $marcas,
        ]);
    }

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