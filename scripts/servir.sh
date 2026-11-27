#!/usr/bin/env bash
# Sert le worktree avec php -S sur un port libre, pour vérifier un écran (docs/agents/verifier-un-ecran.md). Usage : bash scripts/servir.sh [arret | --decalage '<décalage>']

cd "$(git rev-parse --show-toplevel)" || exit 1
suivi=.servir
horloge=.servir-horloge.php

# Arrêt : à lancer avant de rendre la main. Un serveur oublié garde le port et verrouille le dossier du worktree (suppression refusée sous Windows).
if [ "${1:-}" = "arret" ]; then
    if [ -f "$suivi" ]; then
        kill "$(sed -n 1p "$suivi")" 2> /dev/null
        rm -f "$suivi" "$horloge"
        echo "Serveur arrêté."
    else
        echo "Aucun serveur lancé depuis ce dossier."
    fi
    exit 0
fi

# Un seul serveur par worktree : on le réutilise s'il tourne encore.
if [ -f "$suivi" ] && kill -0 "$(sed -n 1p "$suivi")" 2> /dev/null; then
    echo "Déjà servi : http://localhost:$(sed -n 2p "$suivi")/ (pour changer de décalage : bash scripts/servir.sh arret, puis relancer)"
    exit 0
fi

# Premier port libre à partir de 8765 : une autre session peut déjà occuper le suivant (une réponse venue d'un autre dossier faussait les vérifications).
port_libre() { ! (exec 3<> "/dev/tcp/127.0.0.1/$1") 2> /dev/null; }
port=8765
until port_libre "$port"; do port=$((port + 1)); done

# --decalage : horloge décalée pour ce seul serveur (doc technique § 3.8), par exemple '+14 hours' pour voir l'après-midi, sans écrire dans config.php.
# La constante est définie avant config.php ; sa redéfinition n'émet qu'un avertissement, envoyé sur la sortie d'erreur et non dans la page.
options=()
if [ "${1:-}" = "--decalage" ]; then
    php -r 'file_put_contents($argv[1], "<?php const HORLOGE_DECALAGE = " . var_export($argv[2], true) . ";\n");' "$horloge" "${2:?Usage : bash scripts/servir.sh --decalage '+14 hours'}" || exit 1
    options=(-d "auto_prepend_file=$(pwd -W 2> /dev/null || pwd)/$horloge" -d display_errors=stderr)
fi

php "${options[@]}" -S "localhost:$port" -t app/public > /dev/null 2>&1 &
printf '%s\n%s\n' "$!" "$port" > "$suivi"
sleep 1
echo "Servi : http://localhost:$port/ (arrêt : bash scripts/servir.sh arret)"
