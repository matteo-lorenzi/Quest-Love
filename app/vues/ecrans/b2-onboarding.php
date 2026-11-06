<?php
// Vue de l'onboarding — B2 — parcours 1 (appairage) — lot 1 — A.
// Reçoit de OnboardingControleur : $regle (titre et texte), $etape (à partir de 1) et $total.
// Aucune barre de navigation : seules sorties, continuer ou passer.
?>
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
    <!-- « Passer » a le même effet que le bouton du dernier écran. -->
    <form method="post" action="index.php?ecran=onboarding">
        <input type="hidden" name="jeton" value="<?= e($jeton) ?>">
        <button class="lien" type="submit">Passer</button>
    </form>
</main>
