<?php
// Vue de la suppression de compte — F3 — parcours Profil, duo et système — lot 1 — A.
// Reçoit de SupprimerCompteControleur : $partenaire (pseudo, ou null sans duo) et $refus (vrai si la confirmation précédente n'a rien supprimé).
// Écran dédié, exception au panneau bas, construit comme F2 (cadrage § 8.5, § 10). Aucune barre de navigation. Textes d'attente, à relire en S6 (cadrage § 8.3).
?>
<header class="entete-ecran">
    <a class="entete-ecran__retour" href="index.php?ecran=profil" aria-label="Retour">←</a>
    <h1 class="entete-ecran__titre">Supprimer mon compte</h1>
</header>
<main class="supprimer-compte">
    <?php if ($refus): ?>
        <p role="alert">[TEXTE À RÉDIGER : ton compte n'a pas été supprimé, car un nouveau duo vient d'être formé ; confirme à nouveau pour le dissoudre et supprimer ton compte]</p>
    <?php endif ?>
    <p class="supprimer-compte__avertissement">Cette action est irréversible. Ton compte et tout ce qui te concerne seront supprimés.</p>
    <?php if ($partenaire !== null): ?>
        <p>Ton duo avec <?= e($partenaire) ?> sera aussi dissous.</p>
    <?php endif ?>
    <form method="post" action="index.php?ecran=supprimer-compte">
        <input type="hidden" name="jeton" value="<?= e($jeton) ?>">
        <button class="bouton" type="submit">Supprimer mon compte</button>
    </form>
    <a href="index.php?ecran=profil">Annuler</a>
</main>
