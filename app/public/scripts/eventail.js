// Animation de l'éventail — C2 Tirage — parcours 2 (journée de jeu) — lot 1 — B. Amélioration seulement : sans ce script, les cartes restent des liens (doc technique § 4.3).

// Mouvement réduit : rien n'est animé, le lien s'ouvre directement.
if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.querySelectorAll('.eventail .carte-defi--cachee').forEach(function (carte) {
        carte.addEventListener('click', function (evenement) {
            evenement.preventDefault();
            carte.classList.add('carte-defi--sortie');
            // La carte sort du lot, puis le serveur la rend retournée.
            setTimeout(function () { window.location.href = carte.href; }, 200);
        });
    });
}
