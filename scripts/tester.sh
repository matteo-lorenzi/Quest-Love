#!/usr/bin/env bash
# Lance PHPUnit en local (doc technique § 6.4) : prépare ce qui manque et dit clairement ce qui bloque. Usage : bash scripts/tester.sh [options de phpunit]

cd "$(git rev-parse --show-toplevel)" || exit 1

# 1. config.php n'est pas versionné : un worktree neuf n'en a pas. On reprend celui du dossier principal du dépôt.
principal=$(git worktree list --porcelain | sed -n '1s/^worktree //p')
if [ ! -f app/config/config.php ]; then
    if [ -f "$principal/app/config/config.php" ]; then
        cp "$principal/app/config/config.php" app/config/config.php
        echo "config.php copié depuis $principal"
    else
        echo "ÉCHEC : app/config/config.php absent. Le copier depuis config.exemple.php et y mettre les identifiants de la base locale (§ 6.3)." >&2
        exit 1
    fi
fi

# Le graphe graphify n'est pas versionné non plus : un worktree neuf reprend celui du dossier principal (graphify update . le remet à jour).
if [ ! -d graphify-out ] && [ -d "$principal/graphify-out" ]; then
    cp -r "$principal/graphify-out" graphify-out
    echo "graphify-out copié depuis $principal"
fi

# Les skills et agents du projet sont lus dans le dossier principal, même depuis un worktree : s'il est en retard, /cloture-ticket est introuvable.
# Simple avertissement, les tests tournent quand même.
if [ "$(git rev-parse --show-toplevel)" != "$principal" ]; then
    for dossier in .claude/skills .claude/agents; do
        # Absent des deux côtés : rien à comparer (diff sortirait en erreur).
        [ -d "$dossier" ] || [ -d "$principal/$dossier" ] || continue
        if ! diff -rq "$dossier" "$principal/$dossier" > /dev/null 2>&1; then
            echo "ATTENTION : $dossier diffère du dossier principal ($principal). Le mettre à jour (git pull sur main), sinon l'agent ne voit pas les skills du projet." >&2
        fi
    done
fi

# 2. phpunit.phar vit hors du dépôt (§ 6.4) : variable PHPUNIT_PHAR, sinon ~/outils/phpunit.phar, sinon une commande phpunit du PATH.
[ -z "${PHPUNIT_PHAR:-}" ] && [ -f "$HOME/outils/phpunit.phar" ] && PHPUNIT_PHAR="$HOME/outils/phpunit.phar"
if [ -n "${PHPUNIT_PHAR:-}" ] && [ -f "$PHPUNIT_PHAR" ]; then
    lancer=(php "$PHPUNIT_PHAR")
elif command -v phpunit > /dev/null; then
    lancer=(phpunit)
else
    echo "ÉCHEC : phpunit.phar introuvable. Définir PHPUNIT_PHAR avec son chemin complet, par exemple export PHPUNIT_PHAR=/c/Users/moi/outils/phpunit.phar" >&2
    exit 1
fi

# 3. Le serveur MySQL doit répondre, sinon l'amorce des tests échoue sur une trace PDO peu lisible.
#    Sous WAMP, le service wampmysqld64 est démarré au besoin (le compte du poste en a le droit sans être administrateur).
joindre_serveur() {
    php -r 'require "app/config/config.php"; try { new PDO("mysql:host=" . BDD_HOTE, BDD_UTILISATEUR, BDD_MOT_DE_PASSE); } catch (PDOException $e) { fwrite(STDERR, $e->getMessage() . "\n"); exit(1); }'
}
if ! joindre_serveur 2> /dev/null && command -v powershell.exe > /dev/null; then
    echo "MySQL ne répond pas : démarrage du service wampmysqld64."
    powershell.exe -NoProfile -Command 'Start-Service wampmysqld64'
    for _ in 1 2 3 4 5 6 7 8 9 10; do joindre_serveur 2> /dev/null && break; sleep 1; done
fi
if ! joindre_serveur; then
    echo "ÉCHEC : le serveur MySQL ne répond pas (message ci-dessus). Démarrer MySQL (WAMP) et vérifier app/config/config.php." >&2
    exit 1
fi

# 4. Un worktree teste sur sa propre base, pour que deux worktrees testés en même temps ne vident pas la base l'un de l'autre (§ 6.3).
#    Le dossier principal garde la base de config.php, celle de l'application servie.
# TODO(simplif): la base d'un worktree supprimé reste sur le serveur, à effacer à la main (DROP DATABASE) ; nettoyage automatique si elles s'accumulent.
if [ "$(git rev-parse --show-toplevel)" != "$principal" ]; then
    dossier=$(basename "$PWD")
    base="quest_love_$(printf '%s' "$dossier" | tr -c 'a-zA-Z0-9' '_' | cut -c1-50)"
    sed -i "s/^const BDD_NOM = .*/const BDD_NOM = '$base';/" app/config/config.php
    php -r 'require "app/config/config.php"; (new PDO("mysql:host=" . BDD_HOTE, BDD_UTILISATEUR, BDD_MOT_DE_PASSE))->exec("CREATE DATABASE IF NOT EXISTS `" . BDD_NOM . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");' || exit 1
    echo "Base de test du worktree : $base"
fi

# 5. Les tests vident la base : elle est rechargée ensuite, pour que le jeu de démonstration reste servi (§ 6.3).
"${lancer[@]}" "$@"
statut=$?
php sql/reinitialiser.php > /dev/null || echo "ÉCHEC : base non rechargée après les tests (php sql/reinitialiser.php)." >&2
exit "$statut"
