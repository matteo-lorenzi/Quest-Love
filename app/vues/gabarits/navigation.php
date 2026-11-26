<?php
// Gabarit de la barre basse — C1, F1 et écrans principaux — lot 1 — commun. Onglets Accueil et Profil (doc technique § 4.1).
// L'onglet actif est celui de la vue rendue ($vue, posée par Vue::rendre).
// TODO(simplif): Gages et Historique arrivent avec le lot 2 (cadrage § 8.5).
$onglets = ['accueil' => ['Accueil', 'c1-accueil'], 'profil' => ['Profil', 'f1-profil']];
?>
<nav class="navigation" aria-label="Navigation principale">
    <?php foreach ($onglets as $ecran => [$libelle, $vueOnglet]): ?>
        <?php if ($vue === $vueOnglet): ?>
            <a class="navigation__onglet navigation__onglet--actif" href="index.php?ecran=<?= $ecran ?>" aria-current="page"><?= $libelle ?></a>
        <?php else: ?>
            <a class="navigation__onglet" href="index.php?ecran=<?= $ecran ?>"><?= $libelle ?></a>
        <?php endif ?>
    <?php endforeach ?>
</nav>
