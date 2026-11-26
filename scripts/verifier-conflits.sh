#!/usr/bin/env bash
# Refuse les marqueurs de conflit de fusion dans les fichiers texte listés sur l'entrée standard (un chemin par ligne). Appelé par verifier.sh et par le hook pre-commit.

# -I écarte les binaires ; ce script, qui écrit le motif, est exclu.
trouves=$(grep -v '^scripts/verifier-conflits\.sh$' | xargs -d '\n' grep -nIE '^(<{7}|>{7})( |$)|^={7}$' /dev/null 2> /dev/null)
if [ -n "$trouves" ]; then
    printf 'ÉCHEC : marqueur de conflit de fusion :\n%s\n' "$trouves"
    exit 1
fi
