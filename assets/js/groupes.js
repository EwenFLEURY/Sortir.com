// public/js/groupes.js

function initGroupesFilters() {
    const nomInput = document.getElementById('nom-filter');
    const groupesList = document.getElementById('groupes-list');

    const root = document.body;
    if (root.dataset.sortiesBound === '1') return;
    root.dataset.sortiesBound = '1';

    if (
        !nomInput
    ) {
        console.error('Éléments DOM manquants pour les filtres de groupes.');
        return;
    }

    function applyFilter() {
        const nomQuery = nomInput.value.toLowerCase().trim();
        const groupesRows = groupesList.querySelectorAll('tr[data-groupe-id]');

        let visibleCount = 0;

        groupesRows.forEach(row => {
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

document.addEventListener('DOMContentLoaded', initGroupesFilters);
document.addEventListener('turbo:load', initGroupesFilters);
window.addEventListener('pageshow', (e) => { if (e.persisted) initGroupesFilters(); });
