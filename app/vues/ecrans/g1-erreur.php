<?php
// Vue d'erreur — G1 — tous parcours (sortie d'erreur) — lot 1 — B. Reçoit $titre et $texte (selon le code HTTP) d'ErreurControleur.
// Un seul lien de sortie, vers l'entrée de l'application : bienvenue renvoie un joueur connecté vers son étape (spec #122).
// TODO(simplif): illustration de G1 absente, renvoyée au visuel ; l'ajouter avec la passe visuelle (CSS et images).
?>
<main class="erreur">
    <h1 class="erreur__titre"><?= e($titre) ?></h1>
    <p class="erreur__texte"><?= e($texte) ?></p>
    <a class="erreur__lien" href="index.php">[TEXTE À RÉDIGER : retour à l'accueil]</a>
</main>
