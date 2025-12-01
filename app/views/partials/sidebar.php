<?php
$nivelLogado = $_SESSION['user_level'] ?? '';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<div class="sidebar">
  <div class="sidebar-header">
    <div class="sidebar-logo">
      <img src="/TCC/public/images/logos/finalpreta.png" alt="Macaw Systems Logo"
        onclick="window.location='/TCC/index.php?pagina=dashboard'">
    </div>

    <button id="btnAlertas" class="btn-alertas" aria-haspopup="true" aria-expanded="false">
      <i class="fa-solid fa-bell"></i>
      <span id="badge" class="badge" style="display:none">0</span>
    </button>

    <div id="alertasDropdown" class="alertas-dropdown" aria-hidden="true">
      <div class="alertas-header">
        <h3>Alertas</h3>
      </div>
      <div id="alertasLista" class="alertas-list"></div>
    </div>
  </div>

  <div class="sidebar-menu">

    <?php if (in_array($nivelLogado, ['diretor', 'gerente', 'supervisor'])): ?>
      <a href="/TCC/index.php?pagina=dashboard" class="menu-btn">
        <i class="fa-solid fa-gauge"></i> Dashboard
      </a>
    <?php endif; ?>

    <?php if (in_array($nivelLogado, ['diretor', 'gerente', 'supervisor'])): ?>
      <a href="/TCC/index.php?pagina=usuarios" class="menu-btn">
        <i class="fa-solid fa-users"></i> Usuários
      </a>
    <?php endif; ?>

    <div class="menu-section"></div>

    <a href="/TCC/index.php?pagina=estoque" class="menu-btn">
      <i class="fa-solid fa-boxes-stacked"></i> Estoque
    </a>
    <a href="/TCC/index.php?pagina=compras" class="menu-btn">
      <i class="fa-solid fa-cart-shopping"></i> Entradas
    </a>
    <?php if (in_array($nivelLogado, ['operario', 'supervisor', 'gerente', 'diretor'])): ?>
      <a href="/TCC/index.php?pagina=saidas" class="menu-btn">
        <i class="fa-solid fa-truck"></i> Saídas
      </a>
    <?php endif; ?>

    <div class="menu-section"></div>

    <a href="/TCC/index.php?pagina=reposicoes" class="menu-btn">
      <i class="fa-solid fa-box-open"></i> Reposições
    </a>

    <!-- CHECKLIST CORRIGIDO -->
    <?php if (in_array($nivelLogado, ['operario', 'supervisor', 'gerente', 'diretor', 'setor-de-vendas'])): ?>
      <?php
      // setor de vendas vê SAÍDA
      if ($nivelLogado === 'setor-de-vendas') {
        $linkChecklist = "/TCC/index.php?pagina=checklist&tipo=saída";
      }
      // todos os outros veem COMPRA
      else {
        $linkChecklist = "/TCC/index.php?pagina=checklist&tipo=compra";
      }
      ?>
      <a href="<?= $linkChecklist ?>" class="menu-btn">
        <i class="fa-solid fa-clipboard-check"></i> Checklists
      </a>
    <?php endif; ?>
    <!-- FIM CHECKLIST CORRIGIDO -->

    <a href="/TCC/index.php?pagina=solicitacoes" class="menu-btn">
      <i class="fa-solid fa-file-lines"></i> Solicitações
    </a>

    <?php if (in_array($nivelLogado, ['diretor', 'gerente'])): ?>
      <a href="/TCC/index.php?pagina=relatorios" class="menu-btn">
        <i class="fa-solid fa-chart-line"></i> Relatórios
      </a>
    <?php endif; ?>

    <div class="menu-section"></div>

    <a href="/TCC/index.php?pagina=perfil" class="menu-btn">
      <i class="fa-solid fa-gear"></i> Configurações
    </a>
  </div>

  <div class="sidebar-bottom">
    <a href="/TCC/index.php?pagina=logout" class="menu-btn logout-btn">
      <i class="fa-solid fa-right-from-bracket"></i> Sair
    </a>
  </div>
</div>

<script src="/TCC/public/js/darkmode.js"></script>
<script src="/TCC/public/js/acessibilidade.js"></script>
<script>
  (() => {
    const btn = document.getElementById("btnAlertas");
    const drop = document.getElementById("alertasDropdown");
    const listaEl = document.getElementById("alertasLista");
    const badge = document.getElementById("badge");

    // abrir/fechar com stopPropagation seguro
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      const opening = !drop.classList.contains("show");
      if (opening) {
        carregarAlertasIA(); // atualiza sempre que abrir
        drop.classList.add("show");
        btn.setAttribute("aria-expanded", "true");
        drop.setAttribute("aria-hidden", "false");
      } else {
        drop.classList.remove("show");
        btn.setAttribute("aria-expanded", "false");
        drop.setAttribute("aria-hidden", "true");
      }
    });

    // não fecha quando clica dentro
    drop.addEventListener("click", (e) => e.stopPropagation());

    // clicar fora fecha
    document.addEventListener("click", () => {
      if (drop.classList.contains("show")) {
        drop.classList.remove("show");
        btn.setAttribute("aria-expanded", "false");
        drop.setAttribute("aria-hidden", "true");
      }
    });

    // função de marcar como visto
    window.marcarComoVisto = function (id) {
      const card = document.querySelector(`.alerta-card[data-id="${id}"]`);
      if (card) {
        card.classList.add("sumindo");
        setTimeout(() => card.remove(), 280);
      }
      fetch("/TCC/app/controllers/MarcarVistoController.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + encodeURIComponent(id)
      }).then(() => {
        // atualiza badge e lista depois de um tempinho
        setTimeout(carregarAlertasIA, 350);
      });
    };

    // carregar alertas (só pendentes)
    window.carregarAlertasIA = function () {
      fetch("/TCC/app/controllers/MostrarAlertasController.php")
        .then(r => r.json())
        .then(lista => {
          if (!listaEl) return;
          const pendentes = lista.filter(a => a.status === "pendente");
          listaEl.innerHTML = "";

          if (pendentes.length === 0) {
            listaEl.innerHTML = `<div class="alerta-card nenhum">Nenhum alerta pendente!</div>`;
          } else {
            pendentes.forEach(a => {
              listaEl.innerHTML += `
              <div class="alerta-card ${a.tipo || ''}" data-id="${a.id}">
                <div class="icone">🔔</div>
                <div class="conteudo-alerta">
                  <strong>${escapeHtml(a.titulo || 'Alerta')}</strong>
                  <div>${escapeHtml(a.mensagem || '')}</div>
                  <small style="color:#777;display:block;margin-top:6px">Pendente</small>
                </div>
                <button class="botao-visto" onclick="marcarComoVisto(${a.id})">Visualizar</button>
              </div>
            `;
            });
          }

          // atualiza badge
          if (pendentes.length > 0) {
            badge.style.display = "inline-block";
            badge.textContent = pendentes.length;
          } else {
            badge.style.display = "none";
          }
        })
        .catch(err => {
          console.error("Erro ao carregar alertas:", err);
        });
    };

    // helper simples para evitar XSS básico ao injetar strings
    function escapeHtml(str) {
      if (!str) return "";
      return String(str)
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
    }

    // opcional: atualizar a cada 15s (se quiser)
    // const intervalo = setInterval(carregarAlertasIA, 15000);

    // carrega ao iniciar só para manter badge atual
    carregarAlertasIA();
  })();
</script>

<div vw class="enabled">
  <div vw-access-button class="active"></div>
  <div vw-plugin-wrapper>
    <div class="vw-plugin-top-wrapper"></div>
  </div>
</div>

<script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
<script>
  new window.VLibras.Widget('https://vlibras.gov.br/app');
</script>
<svg id="colorblind-filters">
  <!-- PROTANOPIA -->
  <filter id="protanopia">
    <feColorMatrix type="matrix" values="
            0.567 0.433 0     0 0
            0.558 0.442 0     0 0
            0      0.242 0.758 0 0
            0      0      0     1 0" />
  </filter>

  <!-- DEUTERANOPIA -->
  <filter id="deuteranopia">
    <feColorMatrix type="matrix" values="
            0.625 0.375 0     0 0
            0.7   0.3   0     0 0
            0     0.3   0.7   0 0
            0     0     0     1 0" />
  </filter>

  <!-- TRITANOPIA -->
  <filter id="tritanopia">
    <feColorMatrix type="matrix" values="
            0.95  0.05  0     0 0
            0     0.433 0.567 0 0
            0     0.475 0.525 0 0
            0     0     0     1 0" />
  </filter>
</svg>