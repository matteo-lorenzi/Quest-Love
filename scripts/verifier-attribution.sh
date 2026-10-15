#!/usr/bin/env bash
# Refus de toute attribution à un outil d'IA (AGENTS.md, « Commits, PR et tickets ») — texte lu sur l'entrée standard.
# Utilisé par .githooks/commit-msg et par la CI (messages de commit, corps des PR, tickets et commentaires).

motif='Co-Authored-By: *Claude|Claude-Session:|Generated (with|by) \[?Claude Code|claude\.ai/code'

if grep -inE "$motif"; then
    echo "Refus : attribution à un outil d'IA détectée (ligne ci-dessus). Voir AGENTS.md, « Commits, PR et tickets »." >&2
    exit 1
fi
