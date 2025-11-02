<?php
require_once '../controllers/UsuarioController.php';
$controller = new UsuarioController();

$nome = $_GET['nome'] ?? '';
$ativo = $_GET['ativo'] ?? '';
$nivel = $_GET['nivel'] ?? '';
$usuarios = $controller->listarUsuarios($nome, $ativo, $nivel);

foreach ($usuarios as $usuario) {
    $roles = [
        'diretor' => 'Diretor',
        'gerente' => 'Gerente',
        'supervisor' => 'Supervisor',
        'operario' => 'Operário'
    ];
    echo "<tr>
            <td>".htmlspecialchars($usuario['nome'])."</td>
            <td>".htmlspecialchars($usuario['email'])."</td>
            <td>".$roles[$usuario['nivel']]."</td>
            <td>".($usuario['ativo'] ? 'Ativo' : 'Inativo')."</td>
            <td>
                <button class='edit-btn' data-user='".htmlspecialchars(json_encode($usuario), ENT_QUOTES, 'UTF-8')."'>Editar</button>
            </td>
        </tr>";
}
?>