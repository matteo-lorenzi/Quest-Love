<?php
// Vue de l'accueil — C1 — parcours 2 (journée de jeu) — lot 1 — B.
// Reçoit d'AccueilControleur : $etat (EtatAccueil), $pseudo, $partenaire (pseudo ou null), $jeton (jeton CSRF, posé par Vue::rendre), $confirmerNon (panneau « pas aujourd'hui » ouvert) et $message (réponse refusée, ou null).
// Les textes sont des textes d'attente, à relire en S6.
?>
<header class="entete-ecran">
    <h1 class="entete-ecran__titre">Accueil</h1>
</header>
<main class="accueil">
    <?php if ($message !== null): ?>
        <p class="accueil__message" role="alert"><?= e($message) ?></p>
    <?php endif ?>
    <?php require __DIR__ . '/../composants/socle-c1.php' ?>
    <p class="accueil__quitter"><a href="index.php?ecran=quitter-duo&amp;origine=<?= QuitterDuoControleur::parametre(OrigineQuitterDuo::Accueil) ?>">Quitter mon duo</a></p>
    <?php if ($confirmerNon) {
    $question = 'Tu ne joues pas aujourd\'hui ? Ta réponse est définitive : pas de défi pour vous deux aujourd\'hui.';
    $ecranAction = 'accueil';
    $champs = ['reponse' => Reponse::Non->value];
    $libelleConfirmer = 'Confirmer : pas aujourd\'hui';
    $retour = 'accueil';
    require __DIR__ . '/../composants/panneau-confirmation.php';
} ?>
</main>
<?php require __DIR__ . '/../gabarits/navigation.php' ?>
