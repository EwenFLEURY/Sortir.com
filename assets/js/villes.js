// public/js/villes.js

function initVillesFilters() {
    const nomInput = document.getElementById('nom-filter');
    const villesList = document.getElementById('villes-list');

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
        const villesRows = villesList.querySelectorAll('tr[data-ville-id]');

        let visibleCount = 0;

        villesRows.forEach(row => {
            const nom = (row.getAttribute('data-nom') || '').toLowerCase();

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

        if (noResults) {
            noResults.style.display = visibleCount === 0 ? '' : 'none';
        }
    }



    nomInput.addEventListener('input', applyFilter);
    applyFilter();
}

document.addEventListener('DOMContentLoaded', initVillesFilters);
document.addEventListener('turbo:load', initVillesFilters);
window.addEventListener('pageshow', (e) => { if (e.persisted) initVillesFilters(); });
