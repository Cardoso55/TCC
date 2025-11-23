<?php
require_once __DIR__ . '/../models/CompraModel.php';
require_once __DIR__ . '/../models/PedidoReposicaoModel.php';

class ComprasController
{
    /* ===============================
       EXIBE LISTA DE COMPRAS
    =============================== */
       public function getCompras()
    {
        return CompraModel::listarCompras();
    }

    public function index()
    {
        $compras = $this->getCompras();
        require __DIR__ . '/../views/compras.php';
    }

    /* ===============================
       CRIA COMPRA
    =============================== */
    public function salvar()
    {
        session_start();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $fornecedor = trim($_POST['fornecedor']);
            $idUsuario = $_SESSION['id_usuario'] ?? null;

            if (!$idUsuario) {
                die("Usuário não autenticado.");
            }

            // cria compra zerada
            $id_compra = CompraModel::criarCompra($fornecedor, $idUsuario);

            // pega pedidos selecionados
            $pedidosSelecionados = $_POST['pedidos'] ?? [];

            foreach ($pedidosSelecionados as $id_pedido) {
                CompraModel::vincularPedidosACompra($id_compra, $id_pedido);
            }

            // calcula valor total
            CompraModel::atualizarValorTotal($id_compra);

            header("Location: index.php?pagina=compras");
            exit;
        }
    }

    /* ===============================
       MOSTRAR DETALHES DE UMA COMPRA
    =============================== */
  public function detalhes()
{
    if (!isset($_GET['id'])) {
        die("ID da compra não informado.");
    }

    $id = intval($_GET['id']);

    // Busca a compra e garante que existe
    $compra = CompraModel::buscarCompraPorId($id);
    if (!$compra) {
        die("Erro: compra não encontrada.");
    }

    // Busca pedidos vinculados à compra, garante array vazio se não houver
    $pedidos = CompraModel::listarPedidosDaCompra($id) ?? [];

    // Inclui a view
    require __DIR__ . '/../views/detalhes_compra.php';
}

        
}


