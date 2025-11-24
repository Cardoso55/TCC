<?php
require_once __DIR__ . "/../database/conexao.php";

class ChecklistModel {

    public static function listarChecklists($filtros = []) {
    $conn = conectarBanco();
    $query = "SELECT c.*, u.nome AS usuario_nome, p.nome AS produto_nome
              FROM Checklist_TBL c
              LEFT JOIN Usuarios_TBL u ON c.idUsuarios_TBL = u.id_usuario
              LEFT JOIN Produtos_TBL p ON c.idProduto_TBL = p.id_produto
              WHERE 1=1";

    if (!empty($filtros['tipo'])) $query .= " AND c.tipo = '".$conn->real_escape_string($filtros['tipo'])."'";
    if (!empty($filtros['idProduto_TBL'])) $query .= " AND c.idProduto_TBL = ".(int)$filtros['idProduto_TBL'];
    if (!empty($filtros['idCompra_TBL'])) $query .= " AND c.idCompra_TBL = ".(int)$filtros['idCompra_TBL'];
    if (!empty($filtros['idPedidosReposicao_TBL'])) $query .= " AND c.idPedidosReposicao_TBL = ".(int)$filtros['idPedidosReposicao_TBL'];

    // Adiciona ordenação pelos mais recentes primeiro
    $query .= " ORDER BY c.data_criacao DESC";

    $res = $conn->query($query);
    $checklists = $res->fetch_all(MYSQLI_ASSOC);
    $conn->close();
    return $checklists;
}


    public static function criarChecklist($dados) {
    $conn = conectarBanco();

    $tipo = $conn->real_escape_string($dados['tipo']);
    $conteudo = $conn->real_escape_string($dados['conteudo']);
    $idUsuario = (int)$dados['idUsuarios_TBL'];

    // IDs opcionais — garantimos NULL real no SQL
    $idPedido  = !empty($dados['idPedidosReposicao_TBL']) ? (int)$dados['idPedidosReposicao_TBL'] : "NULL";
    $idCompra  = !empty($dados['idCompra_TBL']) ? (int)$dados['idCompra_TBL'] : "NULL";
    $idProduto = !empty($dados['idProduto_TBL']) ? (int)$dados['idProduto_TBL'] : "NULL";

    $query = "
        INSERT INTO Checklist_TBL 
        (tipo, conteudo, status, data_criacao, idUsuarios_TBL, idPedidosReposicao_TBL, idCompra_TBL, idProduto_TBL)
        VALUES ('$tipo', '$conteudo', 'pendente', NOW(), $idUsuario, $idPedido, $idCompra, $idProduto)
    ";

    if ($conn->query($query)) {
        $id = $conn->insert_id;
        $conn->close();
        return ['sucesso' => true, 'id' => $id];
    } else {
        $erro = $conn->error;
        $conn->close();
        return ['erro' => $erro];
    }
}




   public static function confirmarChecklist($idChecklist, $idUsuario, $idPedido) {
    $conn = conectarBanco();
    $idChecklist = (int)$idChecklist;
    $idUsuario = (int)$idUsuario;
    $idPedidoSql = $idPedido !== null ? (int)$idPedido : 'NULL';

    $query = "UPDATE Checklist_TBL
              SET status='concluído', data_confirmacao=NOW(), idUsuarios_TBL=$idUsuario, idPedidosReposicao_TBL=$idPedidoSql
              WHERE id_checklist=$idChecklist";

    if ($conn->query($query)) {
        $conn->close();
        return ['sucesso' => true];
    } else {
        $conn->close();
        return ['erro' => $conn->error];
    }
}



    public static function adicionarObservacao($idChecklist, $observacao) {
        $conn = conectarBanco();
        $idChecklist = (int)$idChecklist;
        $observacao = $conn->real_escape_string($observacao);

        $query = "UPDATE Checklist_TBL SET observacao='$observacao' WHERE id_checklist=$idChecklist";
        $conn->query($query);
        $conn->close();
        return true;
    }

    public static function detalhesChecklist($idChecklist) {
        $conn = conectarBanco();
        $idChecklist = (int)$idChecklist;

        $query = "SELECT c.*, u.nome AS usuario_nome, p.nome AS produto_nome
                  FROM Checklist_TBL c
                  LEFT JOIN Usuarios_TBL u ON c.idUsuarios_TBL = u.id_usuario
                  LEFT JOIN Produtos_TBL p ON c.idProduto_TBL = p.id_produto
                  WHERE c.id_checklist = $idChecklist";

        $res = $conn->query($query);
        $detalhes = $res->fetch_assoc();
        $conn->close();
        return $detalhes;
    }

    public static function todosConfirmadosPara($idCompra = null, $idPedido = null) {
        $conn = conectarBanco();
        $query = "SELECT COUNT(*) AS pendentes FROM Checklist_TBL WHERE status<>'concluído'";
        if ($idCompra) $query .= " AND idCompra_TBL=". (int)$idCompra;
        if ($idPedido) $query .= " AND idPedidosReposicao_TBL=". (int)$idPedido;

        $res = $conn->query($query);
        $row = $res->fetch_assoc();
        $conn->close();
        return $row['pendentes'] == 0;
    }
}
