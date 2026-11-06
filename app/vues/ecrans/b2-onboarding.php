<?php
// Vue de l'onboarding — B2 — parcours 1 (appairage) — lot 1 — A.
// Reçoit de OnboardingControleur : $attente ; variante d'attente : $partenaire (pseudo ou null) ; sinon $regle (titre et texte), $etape (à partir de 1) et $total.
// Aucune barre de navigation : seules sorties, continuer ou passer (variante d'attente : règles complètes ou quitter le duo).
?>
<?php if ($attente): ?>
<main class="onboarding">
    <h1 class="onboarding__titre">En attente de <?= $partenaire !== null ? e($partenaire) : 'ton partenaire' ?></h1>
    <p class="onboarding__texte">Tu as fini les règles. Dès que ton partenaire aura fini les siennes, le jeu s'ouvrira : recharge cette page pour le savoir.</p>
    <a class="bouton" href="index.php?ecran=regles&amp;origine=onboarding&amp;etape=attente">Règles complètes</a>
    <?php // TODO(simplif): l'écran F2 (quitter-duo) n'existe pas, ce lien mène à G1 jusqu'à son écriture (ticket suivant). ?>
    <p><a href="index.php?ecran=quitter-duo">Quitter mon duo</a></p>
</main>
<?php return; endif ?>
<main class="onboarding">
    <p class="onboarding__progression"><?= (int) $etape ?> / <?= (int) $total ?></p>
    <h1 class="onboarding__titre"><?= e($regle['titre']) ?></h1>
    <p class="onboarding__texte"><?= e($regle['texte']) ?></p>
    <?php if ($etape < $total): ?>
        <a class="bouton" href="index.php?ecran=onboarding&amp;etape=<?= (int) $etape + 1 ?>">Suivant</a>
    <?php else: ?>
        <form method="post" action="index.php?ecran=onboarding">
            <input type="hidden" name="jeton" value="<?= e($jeton) ?>">
            <button class="bouton" type="submit">C'est parti</button>
        </form>
    <?php endif ?>
    <a href="index.php?ecran=regles&amp;origine=onboarding&amp;etape=<?= (int) $etape ?>">Règles complètes</a>
    <?php // « Passer » a le même effet que le bouton du dernier écran. ?>
    <form method="post" action="index.php?ecran=onboarding">
        <input type="hidden" name="jeton" value="<?= e($jeton) ?>">
        <button class="lien" type="submit">Passer</button>
    </form>
</main>
