<?php
require_once __DIR__ . "/../database/conexao.php";

class ChecklistModel {

    /* ============================
   CRIAR CHECKLIST
============================= */
public static function criarChecklist($data) {
    $conn = conectarBanco();

    $stmt = $conn->prepare("
        INSERT INTO checklist_tbl (
            tipo,
            conteudo,
            idUsuarios_TBL,
            idPedidosReposicao_TBL,
            idCompra_TBL,
            idProduto_TBL
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssiiii",
        $data['tipo'],
        $data['conteudo'],
        $data['idUsuarios_TBL'],
        $data['idPedidosReposicao_TBL'],
        $data['idCompra_TBL'],
        $data['idProduto_TBL']
    );

    $ok = $stmt->execute();
    $novoId = $stmt->insert_id;

    $stmt->close();
    $conn->close();

    return $ok ? $novoId : false;
}


    /* ============================
       LISTAR ENTRADA / COMPRA
    ============================= */
    public static function listarChecklistsEntrada($filtros = []) {
        $conn = conectarBanco();

        $query = "SELECT c.*, u.nome AS usuario_nome, p.nome AS produto_nome
                  FROM checklist_tbl c
                  LEFT JOIN usuarios_tbl u ON c.idUsuarios_TBL = u.id_usuario
                  LEFT JOIN produtos_tbl p ON c.idProduto_TBL = p.id_produto
                  WHERE c.tipo IN ('entrada', 'compra')";

        if (!empty($filtros['idProduto_TBL'])) {
            $query .= " AND c.idProduto_TBL = " . (int)$filtros['idProduto_TBL'];
        }
        if (!empty($filtros['idPedidosReposicao_TBL'])) {
            $query .= " AND c.idPedidosReposicao_TBL = " . (int)$filtros['idPedidosReposicao_TBL'];
        }
        if (!empty($filtros['idCompra_TBL'])) {
            $query .= " AND c.idCompra_TBL = " . (int)$filtros['idCompra_TBL'];
        }

        $query .= " ORDER BY c.data_criacao DESC";

        $res = $conn->query($query);
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $conn->close();
        return $rows;
    }

    /* ============================
       LISTAR SAÍDA
    ============================= */
    public static function listarChecklistsSaida($filtros = []) {
        $conn = conectarBanco();

        $query = "SELECT c.*, u.nome AS usuario_nome, p.nome AS produto_nome
                  FROM checklist_tbl c
                  LEFT JOIN usuarios_tbl u ON c.idUsuarios_TBL = u.id_usuario
                  LEFT JOIN produtos_tbl p ON c.idProduto_TBL = p.id_produto
                  WHERE LOWER(c.tipo) = 'saida'";

        if (!empty($filtros['idProduto_TBL'])) {
            $query .= " AND c.idProduto_TBL = " . (int)$filtros['idProduto_TBL'];
        }
        if (!empty($filtros['idPedidosSaida_TBL'])) {
            $query .= " AND c.idPedidosSaida_TBL = " . (int)$filtros['idPedidosSaida_TBL'];
        }

        $query .= " ORDER BY c.data_criacao DESC";

        $res = $conn->query($query);
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $conn->close();
        return $rows;
    }

    /* ============================
       BUSCAR POR ID (para confirmar)
    ============================= */
   public static function buscarPorId($idChecklist) {
    $conn = conectarBanco();

    $stmt = $conn->prepare("
        SELECT 
            c.*, 
            u.nome AS usuario_nome, 
            p.nome AS produto_nome,
            e.id_estoque,
            e.quantidade_atual
        FROM checklist_tbl c
        LEFT JOIN usuarios_tbl u ON c.idUsuarios_TBL = u.id_usuario
        LEFT JOIN produtos_tbl p ON c.idProduto_TBL = p.id_produto
        LEFT JOIN estoque_tbl e ON e.idProdutos_TBL = p.id_produto
        WHERE c.id_checklist = ?
    ");
    $stmt->bind_param("i", $idChecklist);
    $stmt->execute();

    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    $stmt->close();
    $conn->close();

    return $data;
}




    /* ============================
       CONFIRMAR CHECKLIST
    ============================= */
    public static function confirmarChecklist($idChecklist, $idUsuario) {
    $conn = conectarBanco();
    $stmt = $conn->prepare("
        UPDATE checklist_tbl
        SET status='concluido',
            data_confirmacao=NOW(),
            idUsuarios_TBL=?
        WHERE id_checklist=?
    ");
    $stmt->bind_param("ii", $idUsuario, $idChecklist);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $ok;
}


    /* ============================
       ADICIONAR OBSERVAÇÃO
    ============================= */
    public static function adicionarObservacao($idChecklist, $obs) {
        $conn = conectarBanco();
        $stmt = $conn->prepare("
            UPDATE checklist_tbl
            SET observacao = ?
            WHERE id_checklist = ?
        ");
        $stmt->bind_param("si", $obs, $idChecklist);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        return true;
    }

    /* ============================
       DETALHES
    ============================= */
    public static function detalhesChecklist($idChecklist) {
        $conn = conectarBanco();

        $stmt = $conn->prepare("
            SELECT c.*, u.nome AS usuario_nome, p.nome AS produto_nome
            FROM checklist_tbl c
            LEFT JOIN usuarios_tbl u ON c.idUsuarios_TBL = u.id_usuario
            LEFT JOIN produtos_tbl p ON c.idProduto_TBL = p.id_produto
            WHERE c.id_checklist = ?
        ");
        $stmt->bind_param("i", $idChecklist);
        $stmt->execute();

        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        $stmt->close();
        $conn->close();

        return $data;
    }

    /* ============================
       VERIFICAR SE TODOS CONFIRMADOS
    ============================= */
    public static function todosConfirmadosPara($idCompra = null, $idReposicao = null) {
        $conn = conectarBanco();

        $query = "SELECT COUNT(*) AS pendentes
                  FROM checklist_tbl
                  WHERE status <> 'concluido'";

        if ($idCompra) {
            $query .= " AND idCompra_TBL = " . (int)$idCompra;
        }
        if ($idReposicao) {
            $query .= " AND idPedidosReposicao_TBL = " . (int)$idReposicao;
        }

        $res = $conn->query($query);
        $row = $res->fetch_assoc();

        $conn->close();
        return $row['pendentes'] == 0;
    }
}
