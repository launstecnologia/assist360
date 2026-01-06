<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Subcategoria;

class ApiController extends Controller
{
    /**
     * Buscar subcategorias por categoria
     */
    public function getSubcategorias(): void
    {
        $categoriaId = $this->input('categoria_id');
        
        if (empty($categoriaId)) {
            $this->json([
                'success' => false,
                'message' => 'ID da categoria é obrigatório'
            ], 400);
            return;
        }
        
        $subcategoriaModel = new Subcategoria();
        $subcategorias = $subcategoriaModel->getByCategoria($categoriaId);
        
        $this->json([
            'success' => true,
            'subcategorias' => $subcategorias
        ]);
    }
}
