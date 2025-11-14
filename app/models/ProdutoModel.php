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

        $stmt = $db->prepare("INSERT INTO produtos_tbl (codigo_produto, nome, categoria, descricao, preco_unitario, imagem_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdss", $codigo, $dados['nome'], $dados['categoria'], $dados['descricao'], $dados['preco'], $imagem_url);
        $stmt->execute();
        $idProduto = $db->insert_id;

        $stmt2 = $db->prepare("INSERT INTO estoque_tbl (idProdutos_TBL, quantidade_atual) VALUES (?, ?)");
        $stmt2->bind_param("ii", $idProduto, $dados['quantidade']);
        $stmt2->execute();

        return $idProduto;
    }

    public static function buscarComEstoque() {
        $db = conectarBanco();
        $res = $db->query("SELECT p.*, e.quantidade_atual, e.quantidade_minima
                           FROM produtos_tbl p
                           LEFT JOIN estoque_tbl e ON p.id_produto = e.idProdutos_TBL");
        return $res->fetch_all(MYSQLI_ASSOC);
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
            SET nome=?, categoria=?, descricao=?, preco_unitario=?, imagem_url=? 
            WHERE id_produto=?");
        $stmt->bind_param("sssdsi", $dados['nome'], $dados['categoria'], $dados['descricao'], $dados['preco'], $imagem_url, $dados['id_produto']);
        $stmt->execute();

        $stmt2 = $db->prepare("UPDATE estoque_tbl SET quantidade_atual=? WHERE idProdutos_TBL=?");
        $stmt2->bind_param("ii", $dados['quantidade'], $dados['id_produto']);
        $stmt2->execute();

        return true;
    }

    public static function excluir($id) {
        $db = conectarBanco();
        $db->query("DELETE FROM estoque_tbl WHERE idProdutos_TBL = $id");
        $db->query("DELETE FROM produtos_tbl WHERE id_produto = $id");
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
            // aceitar números com vírgula/point: normalizar
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

        // Ordenação segura (apenas colunas permitidas)
        $coluna = $filtros['ordenar_por'] ?? null;
        $ordem = strtoupper($filtros['ordem'] ?? 'ASC');

        // 💡 LISTA DE COLUNAS PERMITIDAS AGORA INCLUI 'nome'
        $colunasPermitidas = ['nome', 'preco_unitario', 'quantidade_atual'];

        if (!empty($coluna) && in_array($coluna, $colunasPermitidas)) {
            $ordem = ($ordem === 'DESC') ? 'DESC' : 'ASC'; // Garante apenas ASC ou DESC
            
            // Mapeamento para a coluna correta no SQL
            if ($coluna === 'preco_unitario') {
                $sql .= " ORDER BY p.preco_unitario $ordem";
            } else if ($coluna === 'quantidade_atual') {
                $sql .= " ORDER BY e.quantidade_atual $ordem";
            } else if ($coluna === 'nome') {
                // 🎯 NOVA ORDENAÇÃO POR NOME
                $sql .= " ORDER BY p.nome $ordem";
            }
        } else {
            // Se não houver ordenação, ordena por ID (neutro)
            $sql .= " ORDER BY p.id_produto ASC";
        }

        $stmt = $db->prepare($sql);
        if ($stmt === false) {
            // erro de preparação - útil pra debug
            error_log("MySQL prepare error: " . $db->error);
            return [];
        }

        if (!empty($params)) {
            // bind_param requer referências
            $bind_names = [];
            $bind_names[] = & $types;
            for ($i = 0; $i < count($params); $i++) {
                $bind_names[] = & $params[$i];
            }
            call_user_func_array([$stmt, 'bind_param'], $bind_names);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            error_log("MySQL get_result error: " . $stmt->error);
            return [];
        }
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public static function ordenarPor($campo, $ordem) {
        $db = conectarBanco();
        $campoPermitido = in_array($campo, ['preco', 'quantidade']) ? $campo : 'preco';
        $ordemPermitida = ($ordem === 'desc') ? 'DESC' : 'ASC';

        $sql = "SELECT p.id_produto AS codigo, p.nome, p.tipo, p.preco_unitario AS preco, e.quantidade_atual AS quantidade
                FROM produtos_tbl p
                LEFT JOIN estoque_tbl e ON p.id_produto = e.idProdutos_TBL
                ORDER BY $campoPermitido $ordemPermitida";

        $res = $db->query($sql);
        return $res->fetch_all(MYSQLI_ASSOC);
    }


}
