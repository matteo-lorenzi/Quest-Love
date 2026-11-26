<?php
// Vue de l'écran d'entrée — A1 — parcours 1 (appairage) — lot 1 — A.
// Reçoit de BienvenueControleur : $codeRecu (code d'invitation gardé en session, ou null). Il ne sert qu'à choisir la variante : il n'est jamais affiché.

// Variante « entrée par invitation » : seuls le titre et l'accroche changent, et rien ne nomme l'invitant (cadrage § 6.3).
$invite = $codeRecu !== null;
?>
<main class="bienvenue">
    <?php if ($invite): ?>
        <h1 class="bienvenue__titre">Quelqu'un t'invite à jouer</h1>
        <p class="bienvenue__accroche">Quest &amp; Love est un jeu à deux : chaque jour, tu choisis un défi pour ton partenaire, et lui un pour toi. Crée ton compte ou connecte-toi, ton code d'invitation t'attend en salle d'attente.</p>
    <?php else: ?>
        <h1 class="bienvenue__titre">Quest &amp; Love</h1>
        <p class="bienvenue__accroche">Chaque jour, tu choisis un défi pour ton partenaire, et lui un pour toi. Relève le tien avant minuit, sinon tu perds un point de vie.</p>
    <?php endif ?>

    <nav class="bienvenue__actions" aria-label="Commencer">
        <a class="bouton" href="index.php?ecran=inscription">Créer un compte</a>
        <a class="bouton bouton--secondaire" href="index.php?ecran=connexion">J'ai déjà un compte</a>
    </nav>
</main>
<footer class="bienvenue__pied">
    <!-- TODO(simplif): l'écran des pages légales (A4) n'existe pas, ce lien mène à G1 (404) jusqu'à son ticket. -->
    <a href="index.php?ecran=legal">Mentions légales et confidentialité</a>
</footer>
