<?php

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// ROTA PADRÃO → ir para login
if ($uri === '/' || $uri === '/TCC/') {
    header('Location: app/views/auth/login.php');
    exit;
}

switch ($uri) {

    case '/alertas-ia':
        require __DIR__ . '/app/controllers/MostrarAlertasController.php';
        break;

    // Outras rotas do projeto você coloca aqui:
    // case '/dashboard': require ... break;
    // case '/produtos': require ... break;

    default:
        echo "404 - Rota não encontrada: " . $uri;
        break;
}
