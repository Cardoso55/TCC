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
        $res = $db->query("SELECT p.*, e.quantidade_atual 
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
}
