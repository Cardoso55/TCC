<?php
require_once __DIR__ . '/../models/ComparacoesModel.php';

class ComparacoesController
{
    public function index()
    {
        $model = new ComparacoesModel();
        $dados = $model->compararTudo();

        include __DIR__ . '/../views/comparacoes_view.php';
    }
}
