<?php
require_once __DIR__ . "/../database/conexao.php";

class VendasModel {

    public static function criarSaida($dados) {
        $conn = conectarBanco();

        $idProduto = (int)($dados['id_produto'] ?? 0);
        $quantidade = (int)($dados['quantidade'] ?? 0);
        $observacao = $conn->real_escape_string($dados['observacao'] ?? '');
        $idUsuario = (int)($_SESSION['user_id']); // usuário logado
        $origem = 'vendas';

        if (!$idProduto || !$quantidade) {
            $conn->close();
            return ['erro' => 'Produto ou quantidade inválidos'];
        }

        $query = "INSERT INTO pedidossaida_tbl
                  (id_produto, id_usuario_solicitante, quantidade, origem, observacao)
                  VALUES ($idProduto, $idUsuario, $quantidade, '$origem', '$observacao')";

        if ($conn->query($query)) {
            $id = $conn->insert_id;
            $conn->close();
            return ['sucesso' => true, 'id_pedido_saida' => $id];
        } else {
            $erro = $conn->error;
            $conn->close();
            return ['erro' => $erro];
        }
    }

    public static function listarSaidas() {
        $conn = conectarBanco();

        $query = "SELECT s.id_pedido_saida,
                         s.quantidade,
                         s.status,
                         s.data_pedido,
                         s.observacao,
                         p.nome AS produto_nome,
                         u.nome AS usuario_nome
                  FROM pedidossaida_tbl s
                  LEFT JOIN produtos_tbl p ON s.id_produto = p.id_produto
                  LEFT JOIN usuarios_tbl u ON s.id_usuario_solicitante = u.id_usuario
                  ORDER BY s.data_pedido DESC";

        $res = $conn->query($query);
        $saidas = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $conn->close();
        return $saidas;
    }

    public static function aprovarPedido($idPedido) {
        $conn = conectarBanco();
        $id = (int)$idPedido;
        $query = "UPDATE pedidossaida_tbl SET status = 'a_caminho' WHERE id_pedido_saida = $id";
        $res = $conn->query($query);
        $conn->close();
        return $res ? true : false;
    }

    public static function recusarPedido($idPedido) {
        $conn = conectarBanco();
        $id = (int)$idPedido;

        $query = "UPDATE pedidossaida_tbl SET status = 'negado' WHERE id_pedido_saida = $id";
        $res = $conn->query($query);

        $conn->close();
        return $res ? true : false;
    }

    public static function criarChecklistInicialSaida($idPedido) {
    $conn = conectarBanco();
    $idPedido = (int)$idPedido;

    // Primeiro pega os dados do pedido
    $queryPedido = "SELECT id_produto, quantidade FROM pedidossaida_tbl WHERE id_pedido_saida = $idPedido";
    $res = $conn->query($queryPedido);

    if ($res && $res->num_rows > 0) {
        $pedido = $res->fetch_assoc();
        $idProduto = (int)$pedido['id_produto'];
        $quantidade = (int)$pedido['quantidade'];

        // Conteúdo do checklist
        $conteudo = "Verificar recebimento de $quantidade unidades do produto $idProduto";

        // Insere no checklist
        $queryInsert = "INSERT INTO checklist_tbl
                        (tipo, conteudo, status, idUsuarios_TBL, idPedidosSaida_TBL, idProduto_TBL)
                        VALUES ('saída', '$conteudo', 'pendente', " . (int)$_SESSION['user_id'] . ", $idPedido, $idProduto)";
        $conn->query($queryInsert);
    }

    $conn->close();
}

}
