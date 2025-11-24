<?php
session_start();
require_once __DIR__ . "/../models/IARecommendationsModel.php";

if (!isset($_POST['id'])) {
    echo "Erro: ID não enviado!";
    exit;
}

$model = new IARecommendationsModel();
$id = intval($_POST['id']);
$model->marcarComoVisto($id);

echo "ok";
