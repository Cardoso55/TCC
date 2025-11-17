<?php
require_once "controllers/AlertController.php";

$alert = new AlertController();
$alert->gerarAlertasEstoqueBaixo();
$alert->gerarAlertasProdutoParado();
$alert->gerarAlertasValidade();

echo "Testando alertas...";
