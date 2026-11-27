// Décompte de l'échéance — C3 Défi reçu — parcours 2 (journée de jeu) — lot 1 — B. Amélioration seulement : sans ce script, le temps restant reste le texte écrit par le serveur (doc technique § 4.3).

// Mouvement réduit : le texte du serveur ne change pas.
if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.querySelectorAll('.echeance__reste[data-secondes]').forEach(function (zone) {
        // Durée donnée par le serveur, plus le temps écoulé depuis le chargement : l'heure du téléphone n'entre jamais dans le calcul (doc technique § 3.8).
        // TODO(simplif): un téléphone mis en veille peut suspendre performance.now et retarder le décompte, rechargement manuel ; à revoir si la recette le signale.
        var fin = performance.now() + Number(zone.dataset.secondes) * 1000;
        function afficher() {
            var reste = fin - performance.now();
            if (reste <= 0) {
                // Pas de rechargement : l'expiration se rattrape côté serveur à la requête suivante (cadrage § 6.3).
                zone.textContent = zone.dataset.fin;
                return;
            }
            var minutes = Math.ceil(reste / 60000);
            zone.textContent = Math.floor(minutes / 60) + ' h ' + String(minutes % 60).padStart(2, '0') + ' min';
            // Prochain passage quand la minute affichée change : jamais à la seconde.
            setTimeout(afficher, reste - (minutes - 1) * 60000);
        }
        afficher();
    });
}
