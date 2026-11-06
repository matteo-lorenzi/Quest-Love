<?php
// Vue de la salle d'attente — B1 — lot 1 — A.
// Reçoit de SalleAttenteControleur : $dejaEnDuo ; pour la variante, $partenaire (pseudo ou null) ;
// sinon $code (« XXXX-XXXX »), $echeance, $saisie (à reprendre), $refus (RefusCode ou null) et $attente (heure de réessai ou null).
// Les textes sont à relire en S6 avec les autres textes d'interface.

// Heure au format français, sans les secondes : « 14 h 32 », lu correctement par les lecteurs d'écran.
$heure = fn (DateTimeImmutable $date) => $date->format('G \h i');
$messageErreur = null;
if (!$dejaEnDuo) {
    $messageErreur = match (true) {
        $attente !== null => 'Trop d\'essais de code. Par sécurité, tu pourras réessayer à ' . $heure($attente) . '.',
        $refus === RefusCode::Format => 'Ce texte n\'est pas un code : un code compte 8 caractères, lettres et chiffres.',
        $refus === RefusCode::PropreCode => 'C\'est ton propre code. Donne-le à ton partenaire, et saisis ici le sien.',
        $refus === RefusCode::Expire => 'Ce code a expiré. Demande un nouveau code à ton partenaire.',
        $refus === RefusCode::PasValable => 'Ce code n\'est pas valable. Vérifie-le avec ton partenaire : il a peut-être demandé un nouveau code.',
        default => null,
    };
}
?>
<header class="entete-ecran">
    <!-- TODO(simplif): l'écran profil (F1) n'existe pas, cette flèche mène à G1 jusqu'à ce qu'il soit écrit. -->
    <a class="entete-ecran__retour" href="index.php?ecran=profil" aria-label="Profil">←</a>
    <h1 class="entete-ecran__titre">Salle d'attente</h1>
</header>
<main class="salle-attente">
    <?php if ($dejaEnDuo): ?>
        <p><?= $partenaire !== null ? 'Tu joues déjà avec ' . e($partenaire) . '.' : 'Tu joues déjà en duo.' ?></p>
        <p>Pour rejoindre un autre duo, quitte d'abord ton duo actuel.</p>
        <a class="bouton" href="index.php?ecran=accueil">Retour à l'accueil</a>
        <p><a href="index.php?ecran=quitter-duo&amp;origine=<?= OrigineQuitterDuo::SalleAttente->value ?>">Quitter mon duo</a></p>
    <?php else: ?>
        <?php if ($messageErreur !== null): ?>
            <!-- En tête de page, pour être lue en premier après l'annonce du titre « Erreur : ». -->
            <p class="salle-attente__erreur" id="erreur-code"><?= e($messageErreur) ?></p>
        <?php endif ?>

        <section aria-labelledby="titre-code">
            <h2 id="titre-code">Ton code</h2>
            <p>Donne ce code à ton partenaire : il le saisit de son côté, et votre duo se forme.</p>
            <p class="salle-attente__code"><?= e($code) ?></p>
            <p>Valable une seule fois, jusqu'à <?= e($heure($echeance)) ?>.</p>
            <form method="post" action="index.php?ecran=salle-attente">
                <input type="hidden" name="jeton" value="<?= e($jeton) ?>">
                <input type="hidden" name="action" value="nouveau-code">
                <button class="lien" type="submit">Nouveau code</button>
            </form>
        </section>

        <section aria-labelledby="titre-saisie">
            <h2 id="titre-saisie">Le code de ton partenaire</h2>
            <form class="formulaire" method="post" action="index.php?ecran=salle-attente">
                <input type="hidden" name="jeton" value="<?= e($jeton) ?>">
                <div class="formulaire__champ">
                    <label for="code">Code reçu</label>
                    <!-- Pas de maxlength ni de motif : le copier-coller de « XXXX-XXXX », avec ou sans tiret, doit passer (cadrage § 6.6). -->
                    <input
                        id="code" name="code" type="text" required autocomplete="off" autocapitalize="characters" spellcheck="false"
                        value="<?= e($saisie) ?>"
                        <?php if ($messageErreur !== null): ?>aria-invalid="true" aria-describedby="erreur-code"<?php endif ?>
                    >
                </div>
                <button class="bouton" type="submit">Rejoindre mon partenaire</button>
            </form>
        </section>
    <?php endif ?>
</main>
