<?php
// Composant panneau bas de confirmation — C1 et C3 — lot 1 — commun : question, confirmation, annulation (doc technique § 4.2).
// Reçoit : $question, $ecranAction (écran qui reçoit le POST), $champs (champs cachés, nom => valeur), $libelleConfirmer, $retour (écran de l'annulation), $jeton.
// Ouvert par l'adresse plutôt qu'en JavaScript : l'annulation est un simple lien.
?>
<section class="panneau-confirmation" role="dialog" aria-labelledby="panneau-question">
    <p class="panneau-confirmation__question" id="panneau-question"><?= e($question) ?></p>
    <form method="post" action="index.php?ecran=<?= e($ecranAction) ?>">
        <input type="hidden" name="jeton" value="<?= e($jeton) ?>">
        <?php foreach ($champs as $nom => $valeur): ?>
            <input type="hidden" name="<?= e($nom) ?>" value="<?= e($valeur) ?>">
        <?php endforeach ?>
        <button class="bouton" type="submit"><?= e($libelleConfirmer) ?></button>
    </form>
    <a class="bouton bouton--secondaire" href="index.php?ecran=<?= e($retour) ?>">Annuler</a>
</section>
