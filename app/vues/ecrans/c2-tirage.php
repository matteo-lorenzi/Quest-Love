<?php
// Vue du tirage — C2 — parcours 2 (journée de jeu) — lot 1 — B. Étape « éventail » : trois cartes face cachée, la carte touchée se retourne.
// Reçoit de TirageControleur : $defis (les trois Defi, dans l'ordre du tirage) et $piochee (numéro 1 à 3 de la carte retournée, ou null).
// Sans JavaScript, chaque carte cachée est un lien : atteignable au toucher et au clavier, dans l'ordre du document (cadrage § 9). Textes d'attente, à relire en S6.
?>
<header class="entete-ecran">
    <h1 class="entete-ecran__titre">Pioche le défi de ton partenaire</h1>
</header>
<main class="tirage">
    <ol class="eventail">
        <?php foreach ($defis as $rang => $defi): ?>
            <li class="eventail__place">
                <?php if ($rang + 1 === $piochee): ?>
                    <?php require __DIR__ . '/../composants/carte-defi.php' ?>
                <?php else: ?>
                    <a class="carte-defi carte-defi--cachee" href="index.php?ecran=tirage&amp;carte=<?= $rang + 1 ?>">Carte <?= $rang + 1 ?></a>
                <?php endif ?>
            </li>
        <?php endforeach ?>
    </ol>
    <p><a href="index.php?ecran=accueil">Retour à l'accueil</a></p>
</main>
