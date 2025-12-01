// Ativa ou desativa o dark mode manualmente
function toggleDarkMode() {
    document.body.classList.toggle("dark-mode");

    if (document.body.classList.contains("dark-mode")) {
        localStorage.setItem("theme", "dark");
    } else {
        localStorage.setItem("theme", "light");
    }
}

// Aplica automaticamente o tema salvo
document.addEventListener("DOMContentLoaded", () => {
    if (localStorage.getItem("theme") === "dark") {
        document.body.classList.add("dark-mode");
    }
});

function setDaltonismo(mode) {
    document.body.classList.remove("protanopia-mode", "deuteranopia-mode", "tritanopia-mode");

    if (mode !== "none") {
        document.body.classList.add(`${mode}-mode`);
        localStorage.setItem("daltonismo", mode);
    } else {
        localStorage.removeItem("daltonismo");
    }
}

// Mantém ao trocar de página
document.addEventListener("DOMContentLoaded", () => {
    const saved = localStorage.getItem("daltonismo");
    if (saved) {
        document.body.classList.add(`${saved}-mode`);
    }
});

function toggleHighContrast() {
    document.body.classList.toggle("high-contrast");

    if (document.body.classList.contains("high-contrast")) {
        localStorage.setItem("highContrast", "on");
    } else {
        localStorage.removeItem("highContrast");
    }
}

// Mantém entre páginas
document.addEventListener("DOMContentLoaded", () => {
    if (localStorage.getItem("highContrast") === "on") {
        document.body.classList.add("high-contrast");
    }
});
