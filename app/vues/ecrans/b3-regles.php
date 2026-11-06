<?php
// Vue des règles complètes — B3 — parcours 1 (appairage) — lot 1 — A.
// Reçoit de ReglesControleur : $regles (liste de titre et texte) et $retour (requête du retour, déjà contrôlée).
// Aucune barre de navigation : sous-écran focalisé, seule sortie le retour. Les textes sont à relire en S6.
?>
<header class="entete-ecran">
    <a class="entete-ecran__retour" href="index.php?<?= e($retour) ?>" aria-label="Retour">←</a>
    <h1 class="entete-ecran__titre">Les règles du jeu</h1>
</header>
<main class="regles">
    <p class="regles__intro">Voici tout ce qu'il faut savoir pour jouer à deux.</p>
    <?php foreach ($regles as $i => $regle): ?>
        <section aria-labelledby="regle-<?= (int) $i ?>">
            <h2 id="regle-<?= (int) $i ?>"><?= e($regle['titre']) ?></h2>
            <p><?= e($regle['texte']) ?></p>
        </section>
    <?php endforeach ?>
</main>
