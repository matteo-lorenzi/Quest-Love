<?php
// Vue du défi reçu — C3 — parcours 2 (journée de jeu) — lot 1 — B. Trois sous-états : en cours, confirmation « réalisé » (panneau partagé avec C1), réalisé.
// Reçoit de DefiRecuControleur : $defi (Defi), $missionId, $enCours (sinon réalisé), $confirmerRealise (panneau ouvert), $dateLimite, $secondesRestantes (null si réalisé) et $jeton.
// En cours, l'heure limite et le temps restant sont du texte du serveur, lisible sans JavaScript ; echeance.js le décompte à la minute depuis data-secondes.
// Ni barre basse ni mention de contestabilité, pendant le défi comme après (cadrage § 4.3). Textes d'attente, à relire en S6.
?>
<header class="entete-ecran">
    <h1 class="entete-ecran__titre">Ton défi du jour</h1>
</header>
<main class="defi-recu">
    <?php require __DIR__ . '/../composants/carte-defi.php' ?>
    <?php if ($enCours): ?>
        <?php
        $texteFin = '[TEXTE À RÉDIGER : minuit est passé, recharge la page]';
        // Minute supérieure : 30 s restantes ne s'affichent pas « 0 h 00 min ». Même calcul dans echeance.js.
        $minutes = intdiv($secondesRestantes + 59, 60);
        ?>
        <?php // Pas d'aria-live : le décompte ne s'annonce pas à chaque minute (doc technique § 4.3). ?>
        <p class="echeance">
            À relever avant <time datetime="<?= $dateLimite->format(DATE_ATOM) ?>"><?= $dateLimite->format('H \h i') ?></time>.
            [TEXTE À RÉDIGER : temps restant]
            <span class="echeance__reste" data-secondes="<?= $secondesRestantes ?>" data-fin="<?= e($texteFin) ?>"><?= $secondesRestantes > 0 ? sprintf('%d h %02d min', intdiv($minutes, 60), $minutes % 60) : e($texteFin) ?></span>
        </p>
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
<?php if ($enCours): ?>
    <script src="scripts/echeance.js" defer></script>
<?php endif ?>
