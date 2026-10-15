<?php
// Vue de connexion — A3 — lot 1 — A. Écran de référence : annoté étape par étape avec les numéros du doc technique § 2.2 (§ 2.4).
// Reçoit de ConnexionControleur : $identifiant (saisie à reprendre), $erreurs (champ => code), $attente (heure de réessai ou null).

// Étape 6 — rendu. Textes validés au ticket #3 (RGAA 11.10, WCAG 3.3.1) ; le message d'identifiants faux ne dit pas quel champ est faux (exception de sécurité du WCAG 3.3.3).
$messages = [
    'general' => 'Identifiant ou mot de passe incorrect. Vérifie ta saisie, puis réessaie.',
    'identifiant' => 'Saisis ton identifiant.',
    'mdp' => 'Saisis ton mot de passe.',
];
if ($attente !== null) {
    // Heure au format français, sans les secondes : « 14 h 32 », lu correctement par les lecteurs d'écran.
    $erreurs['general'] = 'attente';
    $messages['general'] = 'Trop d\'essais de connexion. Par sécurité, tu pourras réessayer à ' . $attente->format('G \h i') . '.';
}

// Chaque champ en erreur pointe vers son propre message ; une erreur générale concerne les deux champs.
$decrit = fn (string $champ) => isset($erreurs[$champ]) ? "erreur-$champ" : (isset($erreurs['general']) ? 'erreur-general' : null);
?>
<header class="entete-ecran">
    <a class="entete-ecran__retour" href="index.php?ecran=bienvenue" aria-label="Retour à l'accueil">←</a>
    <h1 class="entete-ecran__titre">Connexion</h1>
</header>
<main class="connexion">
    <?php if (isset($erreurs['general'])): ?>
        <!-- En tête du formulaire, pour être lue en premier après l'annonce du titre « Erreur : ». -->
        <p class="connexion__erreur" id="erreur-general"><?= e($messages['general']) ?></p>
    <?php endif ?>

    <form class="formulaire" method="post" action="index.php?ecran=connexion">
        <p class="formulaire__consigne">Tous les champs sont obligatoires.</p>
        <input type="hidden" name="jeton" value="<?= e($jeton) ?>">

        <?php foreach (['identifiant' => 'Identifiant', 'mdp' => 'Mot de passe'] as $champ => $libelle): ?>
            <div class="formulaire__champ">
                <label for="<?= $champ ?>"><?= $libelle ?></label>
                <!-- autocomplete : les gestionnaires de mots de passe remplissent les deux champs (WCAG 1.3.5 et 3.3.8). -->
                <input
                    id="<?= $champ ?>" name="<?= $champ ?>" required
                    <?php if ($champ === 'identifiant'): ?>
                        type="text" autocomplete="username" value="<?= e($identifiant) ?>"
                    <?php else: ?>
                        type="password" autocomplete="current-password"
                    <?php endif ?>
                    <?php if ($decrit($champ)): ?>
                        aria-invalid="true" aria-describedby="<?= $decrit($champ) ?>"
                    <?php endif ?>
                >
                <?php if (isset($erreurs[$champ])): ?>
                    <p class="formulaire__erreur" id="erreur-<?= $champ ?>"><?= e($messages[$champ]) ?></p>
                <?php endif ?>
            </div>
        <?php endforeach ?>

        <button class="bouton" type="submit">Se connecter</button>
    </form>

    <p class="connexion__inscription"><a href="index.php?ecran=inscription">Pas encore de compte ? Créer un compte</a></p>
</main>
