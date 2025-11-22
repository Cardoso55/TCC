<?php
$nivelLogado = $_SESSION['user_level'] ?? '';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="/TCC/public/images/logos/AZULPRETO.png" alt="Macaw Systems Logo" onclick="window.location='/TCC/index.php?pagina=dashboard'">
        </div>

        

        <button id="btnAlertas" class="btn-alertas" aria-haspopup="true" aria-expanded="false">
            <i class="fa-solid fa-bell"></i>
            <span id="badge" class="badge" style="display:none">0</span>
        </button>

        <div id="alertasDropdown" class="alertas-dropdown" aria-hidden="true">
            <div class="alertas-header">
                <h3>Alertas</h3>
            </div>
            <div id="alertasLista" class="alertas-list">
                <!-- alertas serão injetados aqui -->
            </div>
        </div>
    </div>
    
        <div class="menu-section"></div> <!-- divisória -->

    <!-- BOTÕES PRINCIPAIS -->
    <div class="sidebar-menu">
        <a href="/TCC/index.php?pagina=perfil" class="menu-btn">
            <i class="fa-solid fa-user"></i> Perfil
        </a>

        <?php if (in_array($nivelLogado, ['diretor','gerente','supervisor'])): ?>
            <a href="/TCC/index.php?pagina=usuarios" class="menu-btn">
                <i class="fa-solid fa-users"></i> Usuários
            </a>
        <?php endif; ?>
        
        <div class="menu-section"></div> <!-- divisória -->

        <a href="/TCC/index.php?pagina=estoque" class="menu-btn">
            <i class="fa-solid fa-boxes-stacked"></i> Estoque
        </a>
        <a href="/TCC/index.php?pagina=compras" class="menu-btn">
            <i class="fa-solid fa-cart-shopping"></i> Compras
        </a>

        <div class="menu-section"></div> <!-- divisória -->

        <a href="/TCC/index.php?pagina=reposicoes" class="menu-btn">
            <i class="fa-solid fa-box-open"></i> Reposições
        </a>

        <?php if (in_array($nivelLogado, ['operario','supervisor','gerente','diretor'])): ?>
            <a href="/TCC/index.php?pagina=checklist" class="menu-btn">
                <i class="fa-solid fa-clipboard-check"></i> Checklists
            </a>
        <?php endif; ?>

        <a href="/TCC/index.php?pagina=solicitacoes" class="menu-btn">
            <i class="fa-solid fa-file-lines"></i> Solicitações
        </a>
        <!-- aba de Saídas -->
        <?php if (in_array($nivelLogado, ['operario','supervisor','gerente','diretor'])): ?>
            <a href="/TCC/index.php?pagina=saidas" class="menu-btn">
                <i class="fa-solid fa-truck"></i> Saídas
            </a>
        <?php endif; ?>


        <div class="menu-section"></div> <!-- divisória -->

        <?php if (in_array($nivelLogado, ['diretor','gerente'])): ?>
            <a href="/TCC/index.php?pagina=relatorios" class="menu-btn">
                <i class="fa-solid fa-chart-line"></i> Relatórios
            </a>
        <?php endif; ?>

        <a href="/TCC/index.php?pagina=ia" class="menu-btn">
            <i class="fa-solid fa-robot"></i> IA
        </a>

        <div class="menu-section"></div> <!-- divisória -->

        <a href="/TCC/index.php?pagina=configuracoes" class="menu-btn">
            <i class="fa-solid fa-gear"></i> Configurações
        </a>
    </div>

    <!-- BOTÃO VERMELHO -->
    <div class="sidebar-bottom">
        <a href="/TCC/index.php?pagina=logout" class="menu-btn logout-btn">
            <i class="fa-solid fa-right-from-bracket"></i> Sair
        </a>
    </div>
</div>
