<?php
// Vue des pages légales — A4 — parcours 1 (appairage) — lot 1 — A.
// Reçoit de LegalControleur : $page (clé validée de LegalControleur::PAGES) et $pages (clé => titre), et $titre de Vue::rendre.
// Rubriques du cadrage § 10 « Pages légales » ; leurs textes sont un livrable éditorial (S6), d'ici là en attente de rédaction.

$rubriques = [
    'mentions' => [
        'Éditeur' => "[TEXTE À RÉDIGER : le binôme qui édite le site, dans le cadre du master, et l'université]",
        'Directeur de publication' => '[TEXTE À RÉDIGER : nom du directeur de publication]',
        'Hébergeur' => "[TEXTE À RÉDIGER : serveur Azrael de l'université de Poitiers, adresse et contact de l'hébergeur]",
    ],
    'confidentialite' => [
        'Les données' => "[TEXTE À RÉDIGER : identifiant, pseudo, empreinte du mot de passe, duo et parties ; ni adresse électronique, ni nom, ni date de naissance, ni géolocalisation (cadrage § 10)]",
        'Pourquoi' => '[TEXTE À RÉDIGER : finalité, faire jouer le duo, et rien d\'autre]',
        'Combien de temps' => "[TEXTE À RÉDIGER : jusqu'à la suppression du compte ; empreintes des tentatives de connexion effacées après 30 minutes ; journaux du serveur et sauvegardes de l'hébergeur, avec leurs durées, que la suppression du compte n'efface pas]",
        'Tes droits' => '[TEXTE À RÉDIGER : accès, rectification, effacement (suppression du compte depuis le profil), réclamation auprès de la CNIL]',
        'Contact' => '[TEXTE À RÉDIGER : comment joindre le binôme]',
    ],
    'conditions' => [
        'Les règles du jeu' => '[TEXTE À RÉDIGER : résumé des règles, renvoi aux règles complètes]',
        'Pas de modération' => '[TEXTE À RÉDIGER : les gages sont écrits librement, sans modération ni signalement, dans un duo fermé et consenti]',
        'Ta responsabilité' => '[TEXTE À RÉDIGER : chaque joueur répond de ce qu\'il écrit et des défis qu\'il relève]',
    ],
];
?>
<header class="entete-ecran">
    <?php // Retour vers A1 : un joueur connecté y est renvoyé vers l'écran de son étape d'accès. ?>
    <a class="entete-ecran__retour" href="index.php?ecran=bienvenue" aria-label="Retour">←</a>
    <h1 class="entete-ecran__titre"><?= e($titre) ?></h1>
</header>
<main class="legal">
    <nav class="legal__pages" aria-label="Pages légales">
        <ul>
            <?php foreach ($pages as $cle => $intitule): ?>
                <li><a href="index.php?ecran=legal&amp;page=<?= e($cle) ?>"<?= $cle === $page ? ' aria-current="page"' : '' ?>><?= e($intitule) ?></a></li>
            <?php endforeach ?>
        </ul>
    </nav>
    <?php foreach ($rubriques[$page] as $intitule => $texte): ?>
        <section>
            <h2><?= e($intitule) ?></h2>
            <p><?= e($texte) ?></p>
        </section>
    <?php endforeach ?>
</main>
