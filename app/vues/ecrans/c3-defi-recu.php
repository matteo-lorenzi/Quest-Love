<?php
// Vue du défi reçu — C3 — parcours 2 (journée de jeu) — lot 1 — B. Trois sous-états : en cours, confirmation « réalisé » (panneau partagé avec C1), réalisé.
// Reçoit de DefiRecuControleur : $defi (Defi), $missionId, $enCours (sinon réalisé), $confirmerRealise (panneau ouvert) et $jeton.
// Ni barre basse ni mention de contestabilité, pendant le défi comme après (cadrage § 4.3). Textes d'attente, à relire en S6.
?>
<header class="entete-ecran">
    <h1 class="entete-ecran__titre">Ton défi du jour</h1>
</header>
<main class="defi-recu">
    <?php require __DIR__ . '/../composants/carte-defi.php' ?>
    <?php if ($enCours): ?>
        <p>À relever avant minuit.</p>
        <?php // La déclaration est définitive : elle passe d'abord par le panneau de confirmation (cadrage § 4.3). ?>
        <a class="bouton" href="index.php?ecran=defi-recu&amp;confirmer=realise">J'ai réalisé ce défi</a>
    <?php else: ?>
        <p>Défi réalisé. Bravo !</p>
    <?php endif ?>
    <p><a href="index.php?ecran=accueil">Retour à l'accueil</a></p>
    <?php if ($confirmerRealise) {
        $question = 'Tu as réalisé ce défi ? Ta déclaration est définitive.';
        $ecranAction = 'defi-recu';
        $champs = ['mission' => $missionId];
        $libelleConfirmer = 'Confirmer : défi réalisé';
        $retour = 'defi-recu';
        require __DIR__ . '/../composants/panneau-confirmation.php';
    } ?>
</main>
