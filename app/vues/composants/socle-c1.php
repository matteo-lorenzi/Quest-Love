<?php
// Composant socle de l'accueil — C1 — parcours 2 (journée de jeu) — lot 1 — B. Deux colonnes symétriques, élément commun au centre, zone d'action pleine largeur.
// Reçoit : $etat (EtatAccueil), $pseudo, $partenaire (pseudo ou null), $jeton. Chaque état de l'accueil est une variante de ce socle (doc technique § 4.2).

$nomPartenaire = $partenaire ?? 'ton partenaire';
// Statut sous chaque colonne et élément commun, par état. Le statut du partenaire n'est montré qu'une fois sa réponse utile au joueur.
[$statutJoueur, $statutPartenaire, $centre] = match ($etat) {
    EtatAccueil::Question => ['Pas encore répondu', '', 'On joue aujourd\'hui ?'],
    EtatAccueil::AttentePartenaire => ['Oui', 'Pas encore répondu', 'En attente de ' . $nomPartenaire],
    EtatAccueil::ATirer => ['Oui', 'Oui', 'À toi de piocher'],
    EtatAccueil::DefiEnvoye => ['Défi envoyé', 'Oui', 'En attente de révélation'],
    EtatAccueil::DefiRevele => ['Défi envoyé', 'Défi envoyé', 'Un défi t\'attend'],
    EtatAccueil::JourneeBlanche => ['', '', 'Journée blanche'],
    EtatAccueil::JourneeBlancheSansChoix => ['', '', 'Journée blanche'],
    EtatAccueil::PremierJourDemain => ['', '', 'Premier jour de jeu demain'],
};
?>
<section class="socle-c1" aria-label="Aujourd'hui">
    <div class="socle-c1__colonne">
        <p class="socle-c1__pseudo"><?= e($pseudo) ?></p>
        <p class="socle-c1__statut"><?= e($statutJoueur) ?></p>
    </div>
    <p class="socle-c1__centre"><?= e($centre) ?></p>
    <div class="socle-c1__colonne">
        <p class="socle-c1__pseudo"><?= e($nomPartenaire) ?></p>
        <p class="socle-c1__statut"><?= e($statutPartenaire) ?></p>
    </div>
    <div class="socle-c1__action">
        <?php if ($etat === EtatAccueil::Question): ?>
            <form method="post" action="index.php?ecran=accueil">
                <input type="hidden" name="jeton" value="<?= e($jeton) ?>">
                <button class="bouton" type="submit" name="reponse" value="<?= Reponse::Oui->value ?>">Oui, on joue</button>
            </form>
            <?php // « Pas aujourd'hui » est définitif : il passe d'abord par le panneau de confirmation (cadrage § 8.5). ?>
            <a class="bouton bouton--secondaire" href="index.php?ecran=accueil&amp;confirmer=non">Pas aujourd'hui</a>
        <?php elseif ($etat === EtatAccueil::AttentePartenaire): ?>
            <p>Recharge la page pour savoir si <?= e($nomPartenaire) ?> a répondu.</p>
        <?php elseif ($etat === EtatAccueil::ATirer): ?>
            <a class="bouton" href="index.php?ecran=tirage">Piocher le défi de <?= e($nomPartenaire) ?></a>
        <?php elseif ($etat === EtatAccueil::DefiEnvoye): ?>
            <p>Défi envoyé à <?= e($nomPartenaire) ?>. Ta mission sera révélée dès que <?= e($nomPartenaire) ?> aura pioché la tienne.</p>
        <?php elseif ($etat === EtatAccueil::DefiRevele): ?>
            <?php // Annonce seulement : le défi n'est dévoilé, et la découverte posée, que sur C3 (doc technique § 3.4). ?>
            <p>Vous avez tous les deux choisi : <?= e($nomPartenaire) ?> t'a lancé un défi, à relever avant minuit.</p>
            <a class="bouton" href="index.php?ecran=defi-recu">Voir mon défi</a>
        <?php elseif ($etat === EtatAccueil::JourneeBlanche): ?>
            <p>Pas de défi aujourd'hui : l'un de vous deux a dit non, ou n'avait pas répondu à 12 h. Rendez-vous demain matin.</p>
        <?php elseif ($etat === EtatAccueil::JourneeBlancheSansChoix): ?>
            <p>[TEXTE À RÉDIGER : journée blanche « l'un de vous n'a pas choisi » — il est midi passé et l'un de vous deux n'a pas pioché le défi de l'autre ; aucun défi aujourd'hui, aucun point de vie perdu. Rendez-vous demain matin.]</p>
        <?php elseif ($etat === EtatAccueil::PremierJourDemain): ?>
            <p>Votre duo est prêt, mais il est midi passé : votre premier jour de jeu commence demain. Reviens avant 12 h pour répondre à « On joue aujourd'hui ? ».</p>
            <a class="bouton bouton--secondaire" href="index.php?ecran=regles&amp;origine=<?= ReglesControleur::parametre(OrigineRegles::Accueil) ?>">Règles complètes</a>
        <?php endif ?>
    </div>
</section>
