

function initLieuxFilters() {
    const nomInput = document.getElementById('nom-filter');
    const lieuxList = document.getElementById('lieux-list');

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
        const lieuxRows = lieuxList.querySelectorAll('tr[data-lieu-id]');

        let visibleCount = 0;

        lieuxRows.forEach(row => {
            const nom = (row.dataset.nom || '').toLowerCase();
            const ville = (row.dataset.ville || '').toLowerCase();
            const codePostal = (row.dataset.codepostal || '').toLowerCase();

            const matchesNom = nom.includes(nomQuery) || ville.includes(nomQuery) || codePostal.includes(nomQuery);


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

document.addEventListener('DOMContentLoaded', initLieuxFilters);
document.addEventListener('turbo:load', initLieuxFilters);
window.addEventListener('pageshow', (e) => { if (e.persisted) initLieuxFilters(); });
