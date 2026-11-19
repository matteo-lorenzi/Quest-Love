<?php
// Composant bloc d'événement — D1, D3, D5 — lot 1 — B : titre, texte, action unique (doc technique § 4.2).
// Reçoit : $titre, $texte, $ecranAction (écran de l'action), $libelleAction. L'action est un lien : rien n'est écrit, le routeur ramène à l'événement suivant s'il y en a un.
// TODO(simplif): sans illustration, à ajouter avec les textes définitifs (S6).
?>
<main class="bloc-evenement">
    <h1 class="bloc-evenement__titre"><?= e($titre) ?></h1>
    <p class="bloc-evenement__texte"><?= e($texte) ?></p>
    <a class="bouton" href="index.php?ecran=<?= e($ecranAction) ?>"><?= e($libelleAction) ?></a>
</main>
