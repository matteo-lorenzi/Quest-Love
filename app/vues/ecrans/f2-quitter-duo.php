<?php
// Vue de la dissolution du duo — F2 — parcours 1 (appairage) — lot 1 — A.
// Reçoit de QuitterDuoControleur : $retour (écran d'origine, déjà contrôlé) et $partenaire (pseudo ou null).
// Aucune barre de navigation : sous-écran focalisé. Les textes sont des textes d'attente, à relire en S6.
?>
<header class="entete-ecran">
    <a class="entete-ecran__retour" href="index.php?ecran=<?= e($retour) ?>" aria-label="Retour">←</a>
    <h1 class="entete-ecran__titre">Quitter mon duo</h1>
</header>
<main class="quitter-duo">
    <p><?= $partenaire !== null ? 'Tu es sur le point de quitter ton duo avec ' . e($partenaire) . '.' : 'Tu es sur le point de quitter ton duo.' ?></p>
    <p class="quitter-duo__avertissement">Cette action est irréversible. Les missions, les tirages, les participations et les gages du duo seront supprimés.</p>
    <form method="post" action="index.php?ecran=quitter-duo">
        <input type="hidden" name="jeton" value="<?= e($jeton) ?>">
        <button class="bouton" type="submit">Quitter mon duo</button>
    </form>
    <a href="index.php?ecran=<?= e($retour) ?>">Annuler</a>
</main>
