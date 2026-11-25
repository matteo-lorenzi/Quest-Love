#!/usr/bin/env bash
# Hook PreToolUse (Edit|Write) : rappelle de lire le GOTCHAS.md du dossier avant de modifier app/modeles/, app/controleurs/ ou app/vues/ (AGENTS.md).
# Lit le JSON de l'appel sur l'entrée standard, sans jq (absent du poste) ; les chemins Windows y ont des « \ ».

dossier=$(grep -oE 'app[\/]+(modeles|controleurs|vues)[\/]' | head -1 | sed -E 's/[\/]+/\//g')
[ -z "$dossier" ] && exit 0

printf '{"hookSpecificOutput":{"hookEventName":"PreToolUse","additionalContext":"Rappel : lire %sGOTCHAS.md avant de modifier ce dossier (AGENTS.md), sauf lecture déjà faite dans cette session."}}\n' "$dossier"
