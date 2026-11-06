#!/usr/bin/env bash
# Lance PHPUnit en local (doc technique § 6.4) : prépare ce qui manque et dit clairement ce qui bloque. Usage : bash scripts/tester.sh [options de phpunit]

cd "$(git rev-parse --show-toplevel)" || exit 1

# 1. config.php n'est pas versionné : un worktree neuf n'en a pas. On reprend celui du dossier principal du dépôt.
if [ ! -f app/config/config.php ]; then
    principal=$(git worktree list --porcelain | sed -n '1s/^worktree //p')
    if [ -f "$principal/app/config/config.php" ]; then
        cp "$principal/app/config/config.php" app/config/config.php
        echo "config.php copié depuis $principal"
    else
        echo "ÉCHEC : app/config/config.php absent. Le copier depuis config.exemple.php et y mettre les identifiants de la base locale (§ 6.3)." >&2
        exit 1
    fi
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

# 3. La base locale doit répondre, sinon l'amorce des tests échoue sur une trace PDO peu lisible (WAMP : démarrer MySQL).
if ! php -r 'require "app/config/config.php"; require "app/noyau/Bdd.php"; try { Bdd::connexion(); } catch (PDOException $e) { fwrite(STDERR, $e->getMessage() . "\n"); exit(1); }'; then
    echo "ÉCHEC : la base locale ne répond pas (message ci-dessus). Démarrer MySQL (WAMP) et vérifier app/config/config.php." >&2
    exit 1
fi

exec "${lancer[@]}" "$@"
