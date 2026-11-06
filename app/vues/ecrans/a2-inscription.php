<?php
// Vue d'inscription — A2 — lot 1 — A.
// Reçoit d'InscriptionControleur : $identifiant et $pseudo (saisie à reprendre, jamais le mot de passe), $erreurs (champ => codes) et $attente (heure de réessai si le plafond d'inscriptions est atteint, sinon null).
// Les codes viennent de CompteService::inscrire() ; les textes sont à relire en S6 avec les autres textes d'interface.

$longueurMin = CompteService::IDENTIFIANT_MIN;
$messages = [
    'identifiant' => [
        'longueur' => "L'identifiant doit faire de $longueurMin à " . CompteService::IDENTIFIANT_MAX . ' caractères.',
        'caracteres' => "L'identifiant ne peut contenir que des lettres, des chiffres, des tirets, des tirets bas et des points, sans espace.",
        'pris' => 'Cet identifiant est déjà pris, les majuscules et les accents ne changent rien. Choisis-en un autre.',
        'encodage' => "L'identifiant contient des caractères non reconnus.",
    ],
    'pseudo' => [
        'vide' => 'Saisis un pseudo.',
        'longueur' => 'Le pseudo doit faire ' . CompteService::PSEUDO_MAX . ' caractères au plus.',
        'controle' => 'Le pseudo contient un caractère non autorisé.',
        'encodage' => 'Le pseudo contient des caractères non reconnus.',
    ],
    'mdp' => [
        'longueur' => 'Le mot de passe doit faire de ' . CompteService::MDP_MIN . ' à ' . CompteService::MDP_MAX . ' caractères.',
        'majuscule' => 'Ajoute au moins une majuscule.',
        'minuscule' => 'Ajoute au moins une minuscule.',
        'chiffre' => 'Ajoute au moins un chiffre.',
        'special' => "Ajoute au moins un caractère spécial : ponctuation, symbole, ou une espace au milieu du mot de passe.",
        'espace_bord' => 'Retire l\'espace au début ou à la fin du mot de passe.',
        'controle' => 'Le mot de passe contient un caractère non autorisé, comme une tabulation ou un saut de ligne.',
        'courant' => 'Ce mot de passe est trop courant. Choisis-en un moins prévisible.',
        'contient_identifiant' => 'Le mot de passe ne doit pas contenir ton identifiant.',
        'contient_pseudo' => 'Le mot de passe ne doit pas contenir ton pseudo.',
        'encodage' => 'Le mot de passe contient des caractères non reconnus.',
    ],
];

// Règles rappelées en texte sous chaque champ, toujours affichées (cadrage § 6.6). La CNIL demande d'informer du refus des mots de passe courants.
$regles = [
    'identifiant' => "Il sert à te connecter et ne s'affiche jamais à ton partenaire. De $longueurMin à " . CompteService::IDENTIFIANT_MAX
        . ' caractères : lettres (accents acceptés), chiffres, tiret, tiret bas et point, sans espace.',
    'pseudo' => 'Le nom que ton partenaire verra : prénom ou surnom, de 1 à ' . CompteService::PSEUDO_MAX
        . ' caractères. Plusieurs joueurs peuvent porter le même.',
    'mdp' => 'De ' . CompteService::MDP_MIN . ' à ' . CompteService::MDP_MAX . ' caractères, avec au moins une majuscule, une minuscule, un chiffre'
        . " et un caractère spécial (une espace au milieu compte). Pas d'espace au début ni à la fin, ni ton identifiant ni ton pseudo."
        . ' Les mots de passe les plus courants sont refusés. Le copier-coller est permis.',
];

$champs = ['identifiant' => 'Identifiant', 'pseudo' => 'Pseudo', 'mdp' => 'Mot de passe'];
?>
<header class="entete-ecran">
    <a class="entete-ecran__retour" href="index.php?ecran=bienvenue" aria-label="Retour à l'accueil">←</a>
    <h1 class="entete-ecran__titre">Inscription</h1>
</header>
<main class="inscription">
    <?php if ($codeRecu !== null): ?>
        <!-- Le code d'un lien d'invitation : affiché tel que reçu, il n'est validé que si tu le valides en salle d'attente. -->
        <p class="invitation">Tu as reçu un code d'invitation : <strong><?= e($codeRecu) ?></strong>. Il sera prérempli dans la salle d'attente dès que tu seras connecté.</p>
    <?php endif ?>
    <?php if ($attente !== null): ?>
        <!-- Heure au format français, sans les secondes, comme en A3. -->
        <p class="inscription__erreur" id="erreur-general">Trop d'inscriptions depuis ta connexion. Par sécurité, tu pourras réessayer à <?= e($attente->format('G \h i')) ?>.</p>
    <?php endif ?>
    <form class="formulaire" method="post" action="index.php?ecran=inscription">
        <p class="formulaire__consigne">Tous les champs sont obligatoires.</p>
        <input type="hidden" name="jeton" value="<?= e($jeton) ?>">

        <?php foreach ($champs as $champ => $libelle): ?>
            <?php $codes = $erreurs[$champ] ?? [] ?>
            <div class="formulaire__champ">
                <label for="<?= $champ ?>"><?= $libelle ?></label>
                <?php // La règle est décrite avant l'erreur : elle reste vraie quand l'erreur disparaît. ?>
                <p class="formulaire__regle" id="regle-<?= $champ ?>"><?= e($regles[$champ]) ?></p>
                <?php // autocomplete : un gestionnaire de mots de passe propose et enregistre le compte (WCAG 1.3.5 et 3.3.8). ?>
                <input
                    id="<?= $champ ?>" name="<?= $champ ?>" required
                    <?php if ($champ === 'identifiant'): ?>
                        type="text" autocomplete="username" autocapitalize="off" spellcheck="false"
                        maxlength="<?= CompteService::IDENTIFIANT_MAX ?>" value="<?= e($identifiant) ?>"
                    <?php elseif ($champ === 'pseudo'): ?>
                        <?php // Pas de maxlength : il compte des unités UTF-16, donc coupe un pseudo d'émojis à 15 alors que le service accepte 30 caractères. ?>
                        type="text" autocomplete="nickname" value="<?= e($pseudo) ?>"
                    <?php else: ?>
                        <?php // Pas de maxlength : un collage trop long serait coupé en silence et le joueur enregistrerait un autre mot de passe que le sien. Le service signale la longueur. ?>
                        type="password" autocomplete="new-password"
                    <?php endif ?>
                    aria-describedby="regle-<?= $champ ?><?= $codes ? " erreur-$champ" : '' ?>"
                    <?php if ($codes): ?>aria-invalid="true"<?php endif ?>
                >
                <?php if ($codes): ?>
                    <ul class="formulaire__erreurs" id="erreur-<?= $champ ?>">
                        <?php foreach ($codes as $code): ?>
                            <li class="formulaire__erreur"><?= e($messages[$champ][$code] ?? 'Ce champ est incorrect.') ?></li>
                        <?php endforeach ?>
                    </ul>
                <?php endif ?>
            </div>
        <?php endforeach ?>

        <!-- Visible avant le bouton : sans adresse e-mail, rien ne permet de retrouver un compte (cadrage § 10). -->
        <p class="inscription__avertissement">Sans adresse e-mail, un mot de passe oublié ne peut pas être récupéré : ton compte serait perdu.</p>

        <button class="bouton" type="submit">Créer mon compte</button>
    </form>

    <p class="inscription__connexion"><a href="index.php?ecran=connexion">Déjà un compte ? Se connecter</a></p>
</main>
