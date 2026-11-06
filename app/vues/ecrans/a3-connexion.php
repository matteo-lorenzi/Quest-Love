<?php
// Vue de connexion — A3 — lot 1 — A. Écran de référence : annoté étape par étape avec les numéros du doc technique § 2.2 (§ 2.4).
// Reçoit de ConnexionControleur : $identifiant (saisie à reprendre), $erreurs (champ => code), $attente (heure de réessai ou null).

// Étape 6 — rendu (GET). Le contrôleur a déjà lu la requête et appelé le service ; la vue ne contient aucune règle, seulement de la mise en page.
// Étape 6.1 — textes des erreurs. Textes validés au ticket #3 (RGAA 11.10, WCAG 3.3.1) ; le message d'identifiants faux ne dit pas quel champ est faux (exception de sécurité du WCAG 3.3.3).
$messages = [
    'identifiant' => 'Saisis ton identifiant.',
    'mdp' => 'Saisis ton mot de passe.',
];
// Étape 6.2 — message général, en tête du formulaire : attente en cours, ou identifiants incorrects.
// Heure au format français, sans les secondes : « 14 h 32 », lu correctement par les lecteurs d'écran.
$messageGeneral = match (true) {
    $attente !== null => 'Trop d\'essais de connexion. Par sécurité, tu pourras réessayer à ' . $attente->format('G \h i') . '.',
    isset($erreurs['general']) => 'Identifiant ou mot de passe incorrect. Vérifie ta saisie, puis réessaie.',
    default => null,
};

// Étape 6.3 — liaison des erreurs aux champs. Chaque champ en erreur pointe vers son propre message ; un message général concerne les deux champs.
$decrit = fn (string $champ) => isset($erreurs[$champ]) ? "erreur-$champ" : ($messageGeneral !== null ? 'erreur-general' : null);
?>
<?php // Étape 6.4 — balisage ; tout texte libre passe par e(), seule fonction d'échappement (§ 2.4). ?>
<header class="entete-ecran">
    <a class="entete-ecran__retour" href="index.php?ecran=bienvenue" aria-label="Retour à l'accueil">←</a>
    <h1 class="entete-ecran__titre">Connexion</h1>
</header>
<main class="connexion">
    <?php if ($codeRecu !== null): ?>
        <!-- Le code d'un lien d'invitation : affiché tel que reçu, il n'est validé que si tu le valides en salle d'attente. -->
        <p class="invitation">Tu as reçu un code d'invitation : <strong><?= e($codeRecu) ?></strong>. Il sera prérempli dans la salle d'attente dès que tu seras connecté.</p>
    <?php endif ?>
    <?php if ($messageGeneral !== null): ?>
        <!-- En tête du formulaire, pour être lue en premier après l'annonce du titre « Erreur : ». -->
        <p class="connexion__erreur" id="erreur-general"><?= e($messageGeneral) ?></p>
    <?php endif ?>

    <?php // Étape 6.5 — le POST revient sur ce même écran, avec le jeton CSRF que le routeur vérifiera à son étape 6. ?>
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
                        type="text" autocomplete="username" maxlength="<?= CompteService::IDENTIFIANT_MAX ?>" value="<?= e($identifiant) ?>"
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
