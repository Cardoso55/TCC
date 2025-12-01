/* acessibilidade.js - versão robusta de leitura TTS + zoom */

/* ----------------- ZOOM GLOBAL ----------------- */
const html = document.documentElement;
let fontSize = parseInt(localStorage.getItem("fontSize")) || 16;
html.style.fontSize = fontSize + "px";

window.increaseFont = () => {
    fontSize += 1;
    html.style.fontSize = fontSize + "px";
    localStorage.setItem("fontSize", fontSize);
};
window.decreaseFont = () => {
    if (fontSize > 10) {
        fontSize -= 1;
        html.style.fontSize = fontSize + "px";
        localStorage.setItem("fontSize", fontSize);
    }
};
window.resetFont = () => {
    fontSize = 16;
    html.style.fontSize = "16px";
    localStorage.setItem("fontSize", 16);
};

/* ----------------- UTILITÁRIOS DE TEXTO ----------------- */
function cleanText(s) {
    if (!s) return "";
    return s.replace(/\s+/g, " ").trim();
}

function extractTextFromElement(el) {
    if (!el) return "";

    if (el.tagName === "SCRIPT" || el.tagName === "STYLE") return "";

    if (el.getAttribute && el.getAttribute("aria-label")) {
        const t = cleanText(el.getAttribute("aria-label"));
        if (t) return t;
    }

    if (el.alt) {
        const t = cleanText(el.alt);
        if (t) return t;
    }

    if (el.title) {
        const t = cleanText(el.title);
        if (t) return t;
    }

    if ((el.value !== undefined) && el.value !== null && el.value !== "") {
        const t = cleanText(String(el.value));
        if (t) return t;
    }

    if (el.dataset && el.dataset.readable) {
        const t = cleanText(el.dataset.readable);
        if (t) return t;
    }

    if (el.innerText) {
        const t = cleanText(el.innerText);
        if (t && t.length > 1) return t;
    }

    return "";
}

function findReadableAncestor(el, maxLevels = 3) {
    let node = el;
    let level = 0;
    while (node && level <= maxLevels) {
        const txt = extractTextFromElement(node);
        if (txt && txt.length > 1) return txt;
        node = node.parentElement;
        level++;
    }
    return "";
}

/* ----------------- TTS CORE ----------------- */
function speakText(text) {
    if (!text) return;
    window.speechSynthesis.cancel();
    const u = new SpeechSynthesisUtterance(text);
    u.lang = "pt-BR";
    u.rate = 1;
    u.pitch = 1;
    window.speechSynthesis.speak(u);
}

/* ----------------- LEITURA DE PÁGINA (VISÍVEL) ----------------- */
function getMainContainerText() {
    const selectors = ["main", "#main", "#main-content", ".main-content", "#content", ".content"];
    for (const sel of selectors) {
        const el = document.querySelector(sel);
        if (el) {
            const t = cleanText(el.innerText || el.textContent);
            if (t.length > 5) return t;
        }
    }
    return cleanText(document.body.innerText || document.body.textContent || "");
}

// function lerPaginaInteira() {
//     localStorage.setItem("leitorAtivado", "true");
//     setTimeout(() => {
//         const texto = getMainContainerText();
//         speakText(texto);
//     }, 250);
// }

function pararLeitura() {
    window.speechSynthesis.cancel();
    localStorage.setItem("leitorAtivado", "false");
}

/* ----------------- LEITURA POR CLIQUE (INTERATIVA) ----------------- */

/* 🔥 NOVO: carregamos modo clique do localStorage */
let modoLeituraAtivo = false;

function salvarModoLeitura() {
    localStorage.setItem("modoLeituraPorClique", modoLeituraAtivo ? "1" : "0");
}

function carregarModoLeitura() {
    modoLeituraAtivo = localStorage.getItem("modoLeituraPorClique") === "1";
    if (modoLeituraAtivo) {
        document.body.classList.add("modo-leitura-ativo");
    }
}

/* modificamos o toggle para salvar automaticamente */
function toggleModoLeitura() {
    modoLeituraAtivo = !modoLeituraAtivo;

    if (modoLeituraAtivo) {
        document.body.classList.add("modo-leitura-ativo");
        alert("Modo leitura ativado! Clique em um texto para ouvir.");
    } else {
        document.body.classList.remove("modo-leitura-ativo");
        alert("Modo leitura desativado.");
    }

    salvarModoLeitura(); // <---- O SEGREDO
}

/* ativar leitura por clique */
document.addEventListener("click", (ev) => {
    if (!modoLeituraAtivo) return;

    if (ev.target.closest(".sidebar") || ev.target.closest("nav")) return;

    let texto = extractTextFromElement(ev.target);
    if (!texto) texto = findReadableAncestor(ev.target, 3);

    if (texto && texto.length > 1 && !/^[^\w]+$/.test(texto)) {
        speakText(texto);
    }
}, true);

/* ----------------- AUTO-RESTART PARA LEITURA INTEIRA ----------------- */
function iniciarSeAtivado() {
    const ativo = localStorage.getItem("leitorAtivado") === "true";
    if (ativo) {
        setTimeout(() => {
            const texto = getMainContainerText();
            speakText(texto);
        }, 300);
    }
}

document.addEventListener("DOMContentLoaded", iniciarSeAtivado);
document.addEventListener("readystatechange", iniciarSeAtivado);

/* 🔥 NOVO: restaurar modo leitura por clique em TODAS as páginas */
document.addEventListener("DOMContentLoaded", carregarModoLeitura);

/* ----------------- BOTÃO DO SIDEBAR (LEITURA INTEIRA) ----------------- */
document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("toggleLeitor");
    if (btn) {
        btn.addEventListener("click", () => {
            const ativo = localStorage.getItem("leitorAtivado") === "true";
            if (ativo) {
                pararLeitura();
            } else {
                lerPaginaInteira();
            }
        });
    }
});
