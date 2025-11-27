<?php
require_once __DIR__ . '/../database/conexao.php';

class ProdutoModel {

    public static function salvar($dados, $arquivo) {
        $db = conectarBanco();

        $codigo = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));

        $imagem_url = null;
        if (!empty($arquivo['imagem']['name'])) {
            $pasta = __DIR__ . '/../uploads/';
            if (!file_exists($pasta)) mkdir($pasta, 0777, true);
            $nomeArquivo = time() . "_" . basename($arquivo["imagem"]["name"]);
            $destino = $pasta . $nomeArquivo;
            if (move_uploaded_file($arquivo["imagem"]["tmp_name"], $destino)) {
                $imagem_url = 'uploads/' . $nomeArquivo;
            }
        }


        $stmt = $db->prepare("INSERT INTO produtos_tbl (codigo_produto, nome, categoria, descricao, preco_unitario, valor_compra, imagem_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdsss", $codigo, $dados['nome'], $dados['categoria'], $dados['descricao'], $dados['preco'], $dados['valor_compra'], $imagem_url);
        $stmt->execute();
        $idProduto = $db->insert_id;

        $stmt2 = $db->prepare("INSERT INTO estoque_tbl (idProdutos_TBL, quantidade_atual) VALUES (?, ?)");
        $stmt2->bind_param("ii", $idProduto, $dados['quantidade']);
        $stmt2->execute();

        $db->close();
        return $idProduto;
    }

   public static function buscarComEstoque() {
    $db = conectarBanco();
    $res = $db->query(
        "SELECT p.*, e.quantidade_atual, e.quantidade_minima
         FROM produtos_tbl p
         LEFT JOIN estoque_tbl e ON p.id_produto = e.idProdutos_TBL
         WHERE p.ativo = 1"
    );
    $produtos = $res->fetch_all(MYSQLI_ASSOC);
    $db->close();
    return $produtos;
}


    public static function verificarVinculos($produtoId) {
        // Exemplo de contagem de vínculos
        return [
            'checklists' => self::contarChecklists($produtoId),
            'reposicoes' => self::contarReposicoes($produtoId),
            
        ];
    }

    public static function deletarProduto($produtoId) {
    $conn = conectarBanco();
    $stmt = $conn->prepare("DELETE FROM produtos_tbl WHERE id_produto = ?");
    $stmt->bind_param("i", $produtoId);
    $res = $stmt->execute();
    $stmt->close();
    $conn->close();
    return (bool)$res;
}


    public static function deletarProdutoCascata($produtoId) {
        $conn = conectarBanco();

        // Apaga checklists vinculadas
        $stmt = $conn->prepare("DELETE FROM checklists WHERE produto_id = ?");
        $stmt->execute([$produtoId]);

        // Apaga pedidos de reposição vinculados
        $stmt = $conn->prepare("DELETE FROM reposicoes WHERE produto_id = ?");
        $stmt->execute([$produtoId]);

        // Apaga compras vinculadas (se for necessário)
        $stmt = $conn->prepare("DELETE FROM compras WHERE produto_id = ?");
        $stmt->execute([$produtoId]);

        // Apaga o próprio produto
        self::deletarProduto($produtoId);
    }
     public static function editar($dados, $arquivo) {
        $db = conectarBanco();

        $imagem_url = $dados['imagem_atual'] ?? null;
        if (!empty($arquivo['imagem']['name'])) {
            $pasta = __DIR__ . '/../uploads/';
            if (!file_exists($pasta)) mkdir($pasta, 0777, true);
            $nomeArquivo = time() . "_" . basename($arquivo["imagem"]["name"]);
            $destino = $pasta . $nomeArquivo;
            if (move_uploaded_file($arquivo["imagem"]["tmp_name"], $destino)) {
                $imagem_url = 'uploads/' . $nomeArquivo;
            }
        }

        $stmt = $db->prepare("UPDATE produtos_tbl 
            SET nome=?, categoria=?, descricao=?, preco_unitario=?, valor_compra=?, imagem_url=? 
            WHERE id_produto=?");

        $stmt->bind_param(
            "sssddsi", 
            $dados['nome'], 
            $dados['categoria'], 
            $dados['descricao'], 
            $dados['preco'], 
            $dados['valor_compra'], 
            $imagem_url, 
            $dados['id_produto']
        );
        $stmt->execute();

        $stmt2 = $db->prepare("UPDATE estoque_tbl SET quantidade_atual=? WHERE idProdutos_TBL=?");
        $stmt2->bind_param("ii", $dados['quantidade'], $dados['id_produto']);
        $stmt2->execute();

        $db->close();
        return true;
    }

    
    public static function contarChecklists($produtoId) {
    $conn = conectarBanco();
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM checklist_tbl WHERE idProduto_TBL = ?");
    $stmt->bind_param("i", $produtoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $conn->close();
    return (int)$row['total'];
}

    public static function buscarPreco($idProduto)
    {
        $conn = conectarBanco();

        $sql = "SELECT preco_unitario FROM produtos_tbl WHERE id_produto = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $idProduto);
        $stmt->execute();

        $result = $stmt->get_result();
        $dados = $result->fetch_assoc();

        return $dados ? floatval($dados['preco_unitario']) : 0;
    }


public static function contarReposicoes($produtoId) {
    $conn = conectarBanco();
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM pedidosreposicao_tbl WHERE id_produto = ?");
    $stmt->bind_param("i", $produtoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $conn->close();
    return (int)$row['total'];
}


    public static function excluirPedidosReposicaoDoProduto($id_produto) {
        $db = conectarBanco();
        $id_produto = (int)$id_produto;
        $db->query("DELETE FROM pedidosreposicao_tbl WHERE id_produto = $id_produto");
        $db->close();
    }

 public static function excluir($id_produto) {
    $db = conectarBanco();
    $id_produto = (int)$id_produto;

    $stmt = $db->prepare("UPDATE produtos_tbl SET ativo = 0 WHERE id_produto = ?");
    $stmt->bind_param("i", $id_produto);
    $stmt->execute();
    $stmt->close();

    $db->close();
    return true;
}



    public static function buscarFiltradoComOrdenacao($filtros) {
        $db = conectarBanco();

        $sql = "SELECT p.*, e.quantidade_atual 
                FROM produtos_tbl p
                LEFT JOIN estoque_tbl e ON p.id_produto = e.idProdutos_TBL
                WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($filtros['codigo'])) {
            $sql .= " AND p.codigo_produto LIKE ?";
            $params[] = "%" . $filtros['codigo'] . "%";
            $types .= "s";
        }
        if (!empty($filtros['nome'])) {
            $sql .= " AND p.nome LIKE ?";
            $params[] = "%" . $filtros['nome'] . "%";
            $types .= "s";
        }
        if (!empty($filtros['categoria'])) {
            $sql .= " AND p.categoria LIKE ?";
            $params[] = "%" . $filtros['categoria'] . "%";
            $types .= "s";
        }
        if ($filtros['preco'] !== "" && $filtros['preco'] !== null) {
            $preco = str_replace(',', '.', $filtros['preco']);
            if (is_numeric($preco)) {
                $sql .= " AND p.preco_unitario = ?";
                $params[] = (float)$preco;
                $types .= "d";
            }
        }
        if ($filtros['quantidade'] !== "" && $filtros['quantidade'] !== null) {
            if (is_numeric($filtros['quantidade'])) {
                $sql .= " AND e.quantidade_atual = ?";
                $params[] = (int)$filtros['quantidade'];
                $types .= "i";
            }
        }

        // Ordenação segura
        $coluna = $filtros['ordenar_por'] ?? null;
        $ordem = strtoupper($filtros['ordem'] ?? 'ASC');
        $colunasPermitidas = ['nome', 'preco_unitario', 'quantidade_atual'];

        if (!empty($coluna) && in_array($coluna, $colunasPermitidas)) {
            $ordem = ($ordem === 'DESC') ? 'DESC' : 'ASC';
            if ($coluna === 'preco_unitario') $sql .= " ORDER BY p.preco_unitario $ordem";
            elseif ($coluna === 'quantidade_atual') $sql .= " ORDER BY e.quantidade_atual $ordem";
            elseif ($coluna === 'nome') $sql .= " ORDER BY p.nome $ordem";
        } else {
            $sql .= " ORDER BY p.id_produto ASC";
        }

        $stmt = $db->prepare($sql);
        if ($stmt === false) return [];

        if (!empty($params)) {
            $bind_names = [];
            $bind_names[] = & $types;
            for ($i = 0; $i < count($params); $i++) $bind_names[] = & $params[$i];
            call_user_func_array([$stmt, 'bind_param'], $bind_names);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $produtos = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        $db->close();
        return $produtos;
    }

     public static function atualizarEstoque($idProduto, $quantidade, $tipo = 'entrada') {
            $conn = conectarBanco();
            $idProduto = (int)$idProduto;
            $quantidade = (int)$quantidade;

            // Pega o estoque atual do produto
            $sql = "SELECT id_estoque, quantidade_atual FROM Estoque_TBL WHERE idProdutos_TBL = $idProduto";
            $result = $conn->query($sql);

            if ($result->num_rows == 0) {
                // Se não existir estoque, cria a linha
                $conn->query("INSERT INTO Estoque_TBL (quantidade_atual, idProdutos_TBL) VALUES ($quantidade, $idProduto)");
            } else {
                $row = $result->fetch_assoc();
                $novaQuantidade = $tipo === 'entrada'
                    ? $row['quantidade_atual'] + $quantidade
                    : $row['quantidade_atual'] - $quantidade;

                $conn->query("UPDATE Estoque_TBL SET quantidade_atual = $novaQuantidade, atualizado_em = NOW() WHERE id_estoque = " . $row['id_estoque']);
            }

            $conn->close();
            return true;
        }

        public static function criarMovimentacao($id_produto, $id_usuario, $quantidade, $tipo, $origem, $observacao = null) {
            $db = conectarBanco();

            $stmt = $db->prepare("
                INSERT INTO movimentacoes_tbl
                    (quantidade, tipo, origem, observacao, data_movimentacao, idUsuarios_TBL, idProdutos_TBL)
                VALUES (?, ?, ?, ?, NOW(), ?, ?)
            ");

            $stmt->bind_param("isssii", 
                $quantidade, 
                $tipo, 
                $origem, 
                $observacao, 
                $id_usuario, 
                $id_produto
            );

            $stmt->execute();
            $stmt->close();
            $db->close();
        }
}

   




