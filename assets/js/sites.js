function initSitesFilters() {
    const nomInput = document.getElementById('nom-filter');
    const sitesList = document.getElementById('sites-list');

    const root = document.body;
    if (root.dataset.sortiesBound === '1') return;
    root.dataset.sortiesBound = '1';

    if (
        !nomInput
    ) {
        console.error('Éléments DOM manquants pour les filtres de sorties.');
        return;
    }

    function applyFilter() {
        const nomQuery = nomInput.value.toLowerCase().trim();
        const sitesRows = sitesList.querySelectorAll('tr[data-site-id]');

        let visibleCount = 0;

        sitesRows.forEach(row => {
            const nom = (row.dataset.nom || '').toLowerCase();

            const matchesNom = nom.includes(nomQuery);


            if (
                matchesNom
            ) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
    }

    nomInput.addEventListener('input', applyFilter);
    applyFilter();
}

document.addEventListener('DOMContentLoaded', initSitesFilters);
document.addEventListener('turbo:load', initSitesFilters);
window.addEventListener('pageshow', (e) => { if (e.persisted) initSitesFilters(); });
