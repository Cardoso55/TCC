<!-- Font Awesome (certifique-se de ter apenas um link no <head>) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="/TCC/public/images/logos/VERMELHOPRETO.png" alt="Macaw Systems Logo">
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

    <!-- BOTÕES PRINCIPAIS -->
    <div class="sidebar-menu">
        <a href="/TCC/index.php?pagina=dashboard" class="menu-btn">Dashboard</a>
        <a href="/TCC/index.php?pagina=perfil" class="menu-btn">Perfil</a>
        <a href="/TCC/index.php?pagina=usuarios" class="menu-btn">Usuários</a>
        <a href="/TCC/index.php?pagina=estoque" class="menu-btn">Estoque</a>
        <a href="/TCC/index.php?pagina=compras" class="menu-btn">Compras</a>
        <a href="/TCC/index.php?pagina=reposicoes" class="menu-btn">Reposições</a>
        <a href="/TCC/index.php?pagina=checklists" class="menu-btn">Checklists</a>
        <a href="/TCC/index.php?pagina=solicitacoes" class="menu-btn">Solicitações</a>
        <a href="/TCC/index.php?pagina=relatorios" class="menu-btn">Relatórios</a>
        <a href="/TCC/index.php?pagina=ia" class="menu-btn">IA</a>
        <a href="/TCC/index.php?pagina=configuracoes" class="menu-btn">Configurações</a>

    </div>

    <!-- BOTÃO VERMELHO -->
    <div class="sidebar-bottom">
        <a href="/TCC/index.php?pagina=logout" class="menu-btn logout-btn">Sair</a>
    </div>
</div>

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
      btn.setAttribute("aria-expanded","true");
      drop.setAttribute("aria-hidden","false");
    } else {
      drop.classList.remove("show");
      btn.setAttribute("aria-expanded","false");
      drop.setAttribute("aria-hidden","true");
    }
  });

  // não fecha quando clica dentro
  drop.addEventListener("click", (e) => e.stopPropagation());

  // clicar fora fecha
  document.addEventListener("click", () => {
    if (drop.classList.contains("show")) {
      drop.classList.remove("show");
      btn.setAttribute("aria-expanded","false");
      drop.setAttribute("aria-hidden","true");
    }
  });

  // função de marcar como visto
  window.marcarComoVisto = function(id) {
    const card = document.querySelector(`.alerta-card[data-id="${id}"]`);
    if (card) {
      card.classList.add("sumindo");
      setTimeout(() => card.remove(), 280);
    }
    fetch("/TCC/app/controllers/MarcarVistoController.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id=" + encodeURIComponent(id)
    }).then(()=> {
      // atualiza badge e lista depois de um tempinho
      setTimeout(carregarAlertasIA, 350);
    });
  };

  // carregar alertas (só pendentes)
  window.carregarAlertasIA = function() {
    fetch("/TCC/app/controllers/MostrarAlertasController.php")
      .then(r => r.json())
      .then(lista => {
        if (!listaEl) return;
        const pendentes = lista.filter(a => a.status === "pendente");
        listaEl.innerHTML = "";

        if (pendentes.length === 0) {
          listaEl.innerHTML = `<div class="alerta-card nenhum">🎉 Nenhum alerta pendente!</div>`;
        } else {
          pendentes.forEach(a => {
            listaEl.innerHTML += `
              <div class="alerta-card ${a.tipo||''}" data-id="${a.id}">
                <div class="icone">🔔</div>
                <div class="conteudo-alerta">
                  <strong>${escapeHtml(a.titulo||'Alerta')}</strong>
                  <div>${escapeHtml(a.mensagem||'')}</div>
                  <small style="color:#777;display:block;margin-top:6px">⏳ pendente</small>
                </div>
                <button class="botao-visto" onclick="marcarComoVisto(${a.id})">Marcar como visto</button>
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
