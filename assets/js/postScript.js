// Ajout un évènement de retour en arrière à tous les boutons avec la class goback
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".goback").forEach((el) => {
        el.addEventListener("click", (e) => {
            e.preventDefault(); // évite la navigation si c’est un <a>
            window.history.back();
        });
    });
});

// Actualiser le switch des thèmes
actualiserSwitchTheme();
