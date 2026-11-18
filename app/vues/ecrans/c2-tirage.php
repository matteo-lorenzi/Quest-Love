<?php
// Vue du tirage — C2 — parcours 2 (journée de jeu) — lot 1 — B. Étape « éventail » : trois cartes face cachée ; étape « carte piochée » : la carte touchée se retourne et porte la confirmation du choix.
// Reçoit de TirageControleur : $defis (les trois Defi, dans l'ordre du tirage), $retournee (numéro 1 à 3 de la carte retournée, ou null), $message (journée passée, ou null) et $jeton.
// Passé 12 h, $defis est vide : seul le message est montré.
// Sans JavaScript, chaque carte cachée est un lien et la confirmation un formulaire POST : atteignables au toucher et au clavier, dans l'ordre du document (cadrage § 9).
// eventail.js ne fait qu'animer la sortie de la carte touchée. Textes d'attente, à relire en S6.
?>
<header class="entete-ecran">
    <h1 class="entete-ecran__titre">Pioche le défi de ton partenaire</h1>
</header>
<main class="tirage">
    <?php if ($message !== null): ?>
        <p class="tirage__message" role="alert"><?= e($message) ?></p>
    <?php endif ?>
    <?php if ($defis !== []): ?>
        <ol class="eventail">
            <?php foreach ($defis as $rang => $defi): ?>
                <li class="eventail__place">
                    <?php if ($rang + 1 === $retournee): ?>
                        <?php require __DIR__ . '/../composants/carte-defi.php' ?>
                        <?php // Pas d'action « reposer » : toucher une autre carte remplace celle-ci, et seule la confirmation écrit (zonage). ?>
                        <form method="post" action="index.php?ecran=tirage">
                            <input type="hidden" name="jeton" value="<?= e($jeton) ?>">
                            <input type="hidden" name="carte" value="<?= $retournee ?>">
                            <button class="bouton" type="submit">Attribuer</button>
                        </form>
                    <?php else: ?>
                        <a class="carte-defi carte-defi--cachee" href="index.php?ecran=tirage&amp;carte=<?= $rang + 1 ?>">Carte <?= $rang + 1 ?></a>
                    <?php endif ?>
                </li>
            <?php endforeach ?>
        </ol>
    <?php endif ?>
    <p><a href="index.php?ecran=accueil">Retour à l'accueil</a></p>
</main>
<script src="scripts/eventail.js" defer></script>
