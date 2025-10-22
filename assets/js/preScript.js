/**
 * Vérifie si le thème sombre est mis sur le navigateur
 * @returns {boolean}
 */
function isSystemDarkTheme() {
    return window.matchMedia &&  window.matchMedia("(prefers-color-scheme: dark)").matches;
}

/**
 * Retourne la valeur enregistrer dans le stockage pour le thème
 * @returns {string|null}
 */
function recupererThemeDansStorage() {
    const key = "theme";
    let stored = null;
    try {
        stored = localStorage.getItem(key);
    } catch (e) {}
    return stored;
}

/**
 * Retourne le thème actuel
 * @returns {string}
 */
function recupererThemeActuel() {
    return document.documentElement.getAttribute("data-bs-theme");
}

/**
 * Permet d'enregistrer les préférences de thème dans le stockage
 * @param {string} nouveauTheme
 */
function enregistrerThemeDansStorage(nouveauTheme) {
    const key = "theme";
    localStorage.setItem(key, nouveauTheme);
}

/**
 * Permet de changer le thème du site
 * @param {string} nouveauTheme
 */
function changerTheme(nouveauTheme) {
    console.log('nouveauTheme :', nouveauTheme);
    document.documentElement.setAttribute("data-bs-theme", nouveauTheme);
    enregistrerThemeDansStorage(nouveauTheme);
}

/**
 * Permet d'actualiser le switch des thèmes en fonction du thème actuel
 */
function actualiserSwitchTheme() {
    const switcher = document.getElementById('theme-switcher');
    if (recupererThemeActuel() === 'light') {
        switcher.classList.remove('bi-sun');
        switcher.classList.add('bi-moon');
    } else {
        switcher.classList.add('bi-sun');
        switcher.classList.remove('bi-moon');
    }
}

/**
 * Permet de switcher de thème entre le thème light et dark
 */
function switchTheme() {
    changerTheme(recupererThemeActuel() === 'light' ? 'dark' : 'light');
}

// Applique le thème avant le chargement du CSS
(function () {
    const stored = recupererThemeDansStorage();
    let theme = stored ? stored : isSystemDarkTheme() ? "dark" : "light";
    changerTheme(theme);
})();
