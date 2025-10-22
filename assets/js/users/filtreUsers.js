function initUsersFilters() {
    const filterInput = document.getElementById('filtre-users');
    const usersList = document.getElementById('users-list');

    if (!filterInput) {
        console.error('Éléments DOM manquants pour les filtres de users.');
        return;
    }

    function applyFilter() {
        const filterQuery = filterInput.value.toLowerCase().trim();
        const usersRows = usersList.querySelectorAll('tr[data-id]');

        usersRows.forEach(row => {
            const prenom = (row.dataset.prenom || '').toLowerCase();
            const nom = (row.dataset.nom || '').toLowerCase();
            const username = (row.dataset.username || '').toLowerCase();
            const email = (row.dataset.email || '').toLowerCase();

            console.log(filterQuery, nom, nom.includes(filterQuery));

            const matches =
                prenom.includes(filterQuery)
                || nom.includes(filterQuery)
                || username.includes(filterQuery)
                || email.includes(filterQuery)
            ;

            if (matches) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    filterInput.addEventListener('input', applyFilter);
    applyFilter();
}

document.addEventListener('DOMContentLoaded', initUsersFilters);
window.addEventListener('pageshow', (e) => { if (e.persisted) initUsersFilters(); });
