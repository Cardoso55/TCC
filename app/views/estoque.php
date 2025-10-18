<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque</title>
    <link rel="stylesheet" href="/TCC/public/css/reset.css">
    <link rel="stylesheet" href="/TCC/public/css/sidebar.css">
    <link rel="stylesheet" href="/TCC/public/css/estoque.css">
</head>
<body>
    <div class="all">
        <?php
            include 'partials/sidebar.php'; 
        ?>
        <div class="main-content">

            <h2 class="title">Gestão de Estoque</h2>

            <div class="stock-management">
                <input type="text" placeholder="Pesquisar">
                <button>Filtrar</button>
            </div>
            
            <div class="product-list">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Preço Unitário</th>
                            <th>Quantidade</th>
                            <th><button>Adicionar</button></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>01</td>
                            <td>Água</td>
                            <td>Consumível</td>
                            <td>R$ 3,00</td>
                            <td>50</td>
                            <td>
                                <i class="edit-icon"></i>
                                <i class="delete-icon"></i>
                                <i class="alert-icon"></i>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</body>
</html>