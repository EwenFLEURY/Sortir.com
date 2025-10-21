document.addEventListener('DOMContentLoaded', () => {
    const selectLieu = document.getElementById('sortie_lieu');
    const villeSpan = document.getElementById('ville-name');
    const rueSpan = document.getElementById('lieu-rue');
    const codepSpan = document.getElementById('ville-codep');
    const latitudeSpan = document.getElementById('lieu-latitude');
    const longitudeSpan = document.getElementById('lieu-longitude');
    selectLieu.addEventListener('change', () => {
        const lieuId = selectLieu.value;
        if (!lieuId) {
            villeSpan.textContent = '---';
            rueSpan.textContent = '---';
            codepSpan.textContent = '---';
            latitudeSpan.textContent = '---';
            longitudeSpan.textContent = '---';
            return;
        }
        fetch('/sorties/lieu-info/' + lieuId)
            .then(response => response.json())
            .then(data => {
                villeSpan.textContent = data.villeNom || '---';
                rueSpan.textContent = data.lieuRue || '---';
                codepSpan.textContent = data.lieuCodep || '---';
                latitudeSpan.textContent = data.villeLatitude || '---';
                longitudeSpan.textContent = data.villeLongitude || '---';
            })
            .catch(() => {
                villeSpan.textContent = 'Erreur de chargement';
                rueSpan.textContent = 'Erreur de chargement';
                codepSpan.textContent = 'Erreur de chargement';
                latitudeSpan.textContent = 'Erreur de chargement';
                longitudeSpan.textContent = 'Erreur de chargement';
            });
    });
});