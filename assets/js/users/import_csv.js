(() => {
    const trigger = document.getElementById("csv-trigger");
    const input = document.getElementById("csv-input");
    const status = document.getElementById("csv-status");

    const erreurCouleur = 'text-danger';
    const successCouleur = 'text-success';

    const setStatus = (msg, type = "info") => {
        status.textContent = msg;
        status.dataset.type = type;
        if (type === 'error') {
            status.classList.add(erreurCouleur);
        } else if (type === 'success') {
            status.classList.add(successCouleur);
        }
        else {
            status.classList.remove(erreurCouleur);
            status.classList.remove(successCouleur);
        }
    };

    trigger.addEventListener("click", () => input.click());

    input.addEventListener("change", async () => {
        const file = input.files?.[0];
        if (!file) return;

        if (!/\.csv$/i.test(file.name)) {
            setStatus("Veuillez sélectionner un fichier .csv", "error");
            input.value = "";
            return;
        }

        const url = trigger.dataset.uploadUrl;
        const token = trigger.dataset.csrf;

        const formData = new FormData();
        formData.append("file", file);
        formData.append("_token", token);

        trigger.disabled = true;
        setStatus("Envoi du fichier…");

        try {
            const res = await fetch(url, {
                method: "POST",
                headers: { "X-Requested-With": "XMLHttpRequest" },
                body: formData,
            });

            const data = await res.json();
            if (!res.ok || !data.success) {
                setStatus(`Une erreur est survenue, veuillez consoluter les logs.`, "error");
                throw new Error(data.message || "Erreur inconnue");
            }

            setStatus(data.message || "Fichier reçu, import en cours.", "success");

            setTimeout(() => { location.reload(); }, 2000)
        } catch (e) {
            setStatus(`Erreur: ${e.message}`, "error");
        } finally {
            trigger.disabled = false;
            input.value = "";
        }
    });
})();
