// public/js/sorties.js

function initSortiesFilters() {
    const filterSelect = document.getElementById('site-filter');
    const nomInput = document.getElementById('nom-filter');
    const dateDebutInput = document.getElementById('date-debut-filter');
    const dateFinInput = document.getElementById('date-fin-filter');
    const organisateurCheckbox = document.getElementById('organisateur-filter');
    const inscritCheckbox = document.getElementById('inscrit-filter');
    const pasInscritCheckbox = document.getElementById('pas-inscrit-filter');
    const passeeCheckbox = document.getElementById('passee-filter');
    const resetButton = document.getElementById('reset-filters');
    const sortiesList = document.getElementById('sorties-list');
    const noResults = document.getElementById('no-results'); // si tu l’utilises plus tard

    // Évite de ré-attacher plusieurs fois si Turbo/Back cache recharge
    const root = document.body;
    if (root.dataset.sortiesBound === '1') return;
    root.dataset.sortiesBound = '1';

    if (
        !filterSelect || !nomInput || !dateDebutInput || !dateFinInput ||
        !organisateurCheckbox || !inscritCheckbox || !pasInscritCheckbox ||
        !passeeCheckbox || !resetButton || !sortiesList
    ) {
        console.error('Éléments DOM manquants pour les filtres de sorties.');
        return;
    }

    function applyFilter() {
        const selectedSiteId = filterSelect.value;
        const nomQuery = nomInput.value.toLowerCase().trim();
        const dateDebut = dateDebutInput.value;
        const dateFin = dateFinInput.value;
        const filterOrganisateur = organisateurCheckbox.checked;
        const filterInscrit = inscritCheckbox.checked;
        const filterPasInscrit = pasInscritCheckbox.checked;
        const filterPassee = passeeCheckbox.checked;
        const sortieRows = sortiesList.querySelectorAll('tr[data-site-id]');

        let visibleCount = 0;

        sortieRows.forEach(row => {
            const siteId = row.getAttribute('data-site-id');
            const nom = (row.getAttribute('data-nom') || '').toLowerCase();
            const sortieDate = row.getAttribute('data-date');
            const isOrganisateur = row.getAttribute('data-organisateur') === 'true';
            const isInscrit = row.getAttribute('data-inscrit') === 'true';
            const isPassee = row.getAttribute('data-passee') === 'true';

            const matchesSite = selectedSiteId === '' || siteId === selectedSiteId;
            const matchesNom = nom.includes(nomQuery);
            const matchesDateDebut = !dateDebut || sortieDate >= dateDebut;
            const matchesDateFin = !dateFin || sortieDate <= dateFin;
            const matchesOrganisateur = !filterOrganisateur || isOrganisateur;
            const matchesInscrit = !filterInscrit || isInscrit;
            const matchesPasInscrit = !filterPasInscrit || !isInscrit;
            const matchesPassee = !filterPassee || isPassee;

            if (
                matchesSite && matchesNom && matchesDateDebut && matchesDateFin &&
                matchesOrganisateur && matchesInscrit && matchesPasInscrit && matchesPassee
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

    function resetFilters() {
        filterSelect.value = '';
        nomInput.value = '';
        dateDebutInput.value = '';
        dateFinInput.value = '';
        organisateurCheckbox.checked = false;
        inscritCheckbox.checked = false;
        pasInscritCheckbox.checked = false;
        passeeCheckbox.checked = false;
        applyFilter();
    }

    filterSelect.addEventListener('change', applyFilter);
    nomInput.addEventListener('input', applyFilter);
    dateDebutInput.addEventListener('change', applyFilter);
    dateFinInput.addEventListener('change', applyFilter);
    organisateurCheckbox.addEventListener('change', applyFilter);
    inscritCheckbox.addEventListener('change', applyFilter);
    pasInscritCheckbox.addEventListener('change', applyFilter);
    passeeCheckbox.addEventListener('change', applyFilter);
    resetButton.addEventListener('click', resetFilters);

    // Premier passage
    applyFilter();
}

document.addEventListener('DOMContentLoaded', initSortiesFilters);
