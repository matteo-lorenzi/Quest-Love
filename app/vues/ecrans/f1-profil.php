<?php
// Vue du profil — F1 — parcours Profil, duo et système — lot 1 — A.
// Reçoit de ProfilControleur : $pseudo et $identifiant (du joueur connecté), $carteDuo (ligne du duo et « Quitter mon duo »),
// $partenaire (pseudo seul, cadrage § 10), $barre (barre basse) et $retour (écran de la flèche ←, ou null).
// Textes d'attente, à relire en S6 (cadrage § 8.3). Un texte trop long n'est jamais coupé ici : la classe texte-coupe ajoute « … ».
?>
<header class="entete-ecran">
    <?php if ($retour !== null): ?>
        <a class="entete-ecran__retour" href="index.php?ecran=<?= e($retour) ?>" aria-label="Retour">←</a>
    <?php endif ?>
    <h1 class="entete-ecran__titre">Mon profil</h1>
</header>
<main class="profil">
    <p class="texte-coupe"><strong><?= e($pseudo) ?></strong></p>
    <p>
        <span class="liste-liens__precision">Ton identifiant · celui qui t'ouvre la porte</span><br>
        <span class="texte-coupe"><?= e($identifiant) ?></span>
    </p>
    <?php if ($carteDuo): ?>
        <p class="texte-coupe">Tu es en duo avec <?= e($partenaire) ?></p>
    <?php endif ?>

    <ul class="liste-liens">
        <li><a class="liste-liens__lien" href="index.php?ecran=regles&amp;origine=<?= ReglesControleur::parametre(OrigineRegles::Profil) ?>">Règles du jeu <span class="liste-liens__precision">· un petit rappel des règles ?</span></a></li>
        <!-- TODO(simplif): A4 · pages légales n'existe pas, ce lien mène à G1 (404) jusqu'à son lot. -->
        <li><a class="liste-liens__lien" href="index.php?ecran=legal">Pages légales <span class="liste-liens__precision">· les petits caractères</span></a></li>
        <li>
            <!-- TODO(simplif): A1 · bienvenue n'existe pas, la déconnexion y redirige donc vers G1 (404) jusqu'à son lot. -->
            <form method="post" action="index.php?ecran=deconnexion">
                <input type="hidden" name="jeton" value="<?= e($jeton) ?>">
                <button class="liste-liens__lien" type="submit">Se déconnecter <span class="liste-liens__precision">· à demain ?</span></button>
            </form>
        </li>
    </ul>

    <section class="zone-sensible" aria-labelledby="titre-sensible">
        <h2 id="titre-sensible" class="zone-sensible__titre">Ici, on réfléchit avant de cliquer</h2>
        <?php if ($carteDuo): ?>
            <a class="liste-liens__lien" href="index.php?ecran=quitter-duo&amp;origine=<?= QuitterDuoControleur::parametre(OrigineQuitterDuo::Profil) ?>">Quitter mon duo</a>
        <?php endif ?>
        <!-- TODO(simplif): F3 · suppression du compte n'existe pas, ce lien mène à G1 (404) jusqu'à son lot. -->
        <a class="liste-liens__lien zone-sensible__discret" href="index.php?ecran=supprimer-compte">Supprimer mon compte</a>
    </section>
</main>
<?php if ($barre) {
    require __DIR__ . '/../gabarits/navigation.php';
} ?>
