<?php
require_once __DIR__ . "/../database/conexao.php"; // seu arquivo de conexão

class IARecommendationsModel {
    private $conn;

    public function __construct() {
        $this->conn = conectarBanco();
    }

    public function getRecomendacoesNaoVistas() {
        $sql = "SELECT * FROM ia_recomendacoes_tbl WHERE visto = 0 ORDER BY criado_em DESC";
        $res = $this->conn->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function marcarComoVisto($id) {
        $stmt = $this->conn->prepare("UPDATE ia_recomendacoes_tbl SET visto = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}
