#!/usr/bin/env bash
#
# Installation guidée de l'environnement local (WAMP, PHP 8.2, base quest_love) — aucun écran — lot 1 — commun.
# Usage, dans Git Bash : bash scripts/installer-wamp.sh (doc technique § 6.3).
#
# Everything above the "STAGES" marker is the wizard library: do not hand-edit
# it. Author the per-step stages below the marker.

set -euo pipefail

# ──────────────────────────────────────────────────────────────────────────
# Wizard library: delightful, consistent UX, identical across every wizard.
# ──────────────────────────────────────────────────────────────────────────

if [[ -t 1 ]] && command -v tput >/dev/null 2>&1 && [[ "$(tput colors 2>/dev/null || echo 0)" -ge 8 ]]; then
  BOLD=$(tput bold); DIM=$(tput dim); RESET=$(tput sgr0)
  BLUE=$(tput setaf 4); GREEN=$(tput setaf 2); YELLOW=$(tput setaf 3)
else
  BOLD=""; DIM=""; RESET=""; BLUE=""; GREEN=""; YELLOW=""
fi

# Author sets this at the top of the stages section.
TOTAL_STAGES=0

_STAGE_INDEX=0
ENV_FILE="${ENV_FILE:-.env}"
WRITTEN_ENV=()    # KEYs written to ENV_FILE this run
WRITTEN_SECRET=() # secret NAMEs set this run
SKIPPED=()        # things we couldn't do (e.g. gh missing)

# _clear wipes the terminal so only the current step is on screen. No-op when
# output isn't a terminal, so piped logs stay readable.
_clear() {
  [[ -t 1 ]] || return 0
  if command -v tput >/dev/null 2>&1 && tput clear 2>/dev/null; then return 0; fi
  printf '\033[2J\033[3J\033[H'
}

# banner "Title" shows the opening frame: what this wizard does.
banner() {
  _clear
  printf '\n%s%s  %s%s\n' "$BOLD" "$BLUE" "$1" "$RESET"
  printf '%s  %s stages%s\n\n' "$DIM" "$TOTAL_STAGES" "$RESET"
  printf '%s  You drive the browser; this wizard tells you exactly what to do and\n' "$DIM"
  printf '  captures the values you copy back. Stop any time with Ctrl-C and re-run\n'
  printf '  later, since it remembers values already saved.%s\n' "$RESET"
  pause "Ready to start?"
}

# stage "Name" clears the screen, then announces a stage and shows progress.
# Clearing keeps only the current step on screen.
stage() {
  _clear
  _STAGE_INDEX=$((_STAGE_INDEX + 1))
  printf '\n%s%s▸ Stage %s/%s · %s%s\n' \
    "$BOLD" "$BLUE" "$_STAGE_INDEX" "$TOTAL_STAGES" "$1" "$RESET"
}

# say "..." prints a plain instruction line.
say()  { printf '  %s\n' "$1"; }
# step "..." is a numbered-feeling action the human takes in the browser.
step() { printf '  %s•%s %s\n' "$BLUE" "$RESET" "$1"; }
note() { printf '  %s%s%s\n' "$DIM" "$1" "$RESET"; }
warn() { printf '  %s⚠ %s%s\n' "$YELLOW" "$1" "$RESET"; }

# open_url URL opens it in the human's browser, cross-platform incl. WSL.
open_url() {
  local url="$1"
  printf '  %s↗ opening%s %s\n' "$GREEN" "$RESET" "$url"
  { if   command -v wslview     >/dev/null 2>&1; then wslview "$url"
    elif command -v explorer.exe >/dev/null 2>&1; then explorer.exe "$url"
    elif command -v xdg-open    >/dev/null 2>&1; then xdg-open "$url"
    elif command -v open        >/dev/null 2>&1; then open "$url"
    else false; fi
  } >/dev/null 2>&1 || warn "couldn't open a browser, so visit it manually: $url"
}

# pause "msg" waits for the human to confirm they've done the manual part.
pause() {
  printf '  %s%s%s ' "$DIM" "${1:-Press Enter to continue}" "$RESET"
  read -r _ || true
}

# confirm "question" is a y/N gate; returns success on yes.
confirm() {
  local reply=""
  printf '  %s? %s [y/N] ' "$YELLOW" "$1"
  read -r reply || true
  [[ "$reply" =~ ^[Yy] ]]
}

# _existing KEY: current value of KEY in ENV_FILE, if any, with write_env's single
# quotes or a plain dotenv double-quoted value undone.
_existing() {
  [[ -f "$ENV_FILE" ]] || return 1
  local line value sq="'\\''"; line=$(grep -E "^${1}=" "$ENV_FILE" | tail -n1) || return 1
  value="${line#*=}"
  if [[ "$value" == \'*\' ]]; then value="${value:1:${#value}-2}"; value="${value//"$sq"/\'}"
  elif [[ "$value" == \"*\" ]]; then
    value="${value:1:${#value}-2}"
    [[ "$value" == *[\\\$\"]* ]] && return 1   # escapes, $ or ": can't decode safely; ask again
  fi
  printf '%s' "$value"
}

# ask KEY "Prompt" reads a value into $KEY. Offers the existing .env value as
# a default on re-runs (Enter keeps it). Visible input (non-secret), edited
# with Readline. Fails at EOF with no input.
ask() {
  local key="$1" prompt="$2" current input s=$'\001' e=$'\002' p rc=0
  current=$(_existing "$key" || true)
  # Readline needs the colour codes wrapped in \001..\002 to place the cursor.
  p="  $s$BOLD$e$prompt$s$RESET$e "
  [[ -n "$current" ]] && p+="$s$DIM$e[Enter keeps current]$s$RESET$e "
  read -e -r -p "$p" input || rc=1
  if (( rc )) && [[ -z "$input" ]]; then return 1; fi
  [[ -z "$input" && -n "$current" ]] && input="$current"
  printf -v "$key" '%s' "$input"
}

# ask_secret KEY "Prompt" is like ask, but input is hidden.
ask_secret() {
  local key="$1" prompt="$2" current input rc=0
  current=$(_existing "$key" || true)
  if [[ -n "$current" ]]; then
    printf '  %s%s%s %s[Enter keeps current]%s ' "$BOLD" "$prompt" "$RESET" "$DIM" "$RESET"
  else
    printf '  %s%s%s ' "$BOLD" "$prompt" "$RESET"
  fi
  read -rs input || rc=1
  printf '\n'
  if (( rc )) && [[ -z "$input" ]]; then return 1; fi
  [[ -z "$input" && -n "$current" ]] && input="$current"
  printf -v "$key" '%s' "$input"
}

# write_env KEY VALUE upserts KEY='VALUE' into ENV_FILE (replaces any existing
# line) and sets $KEY. Idempotent. Creates ENV_FILE at mode 0600; writes through
# a symlink and keeps an existing file's mode.
write_env() {
  local key="$1" value="$2" tmp sq="'\\''"
  [[ -e "$ENV_FILE" ]] || (umask 077 && : > "$ENV_FILE")
  tmp=$(mktemp)
  grep -vE "^${key}=" "$ENV_FILE" > "$tmp" || true
  printf "%s='%s'\n" "$key" "${value//\'/$sq}" >> "$tmp"
  cat "$tmp" > "$ENV_FILE"
  rm -f "$tmp"
  printf -v "$key" '%s' "$value"
  WRITTEN_ENV+=("$key")
  printf '  %s✓ wrote%s %s → %s\n' "$GREEN" "$RESET" "$key" "$ENV_FILE"
}

# set_secret NAME VALUE sets a GitHub Actions repo secret via gh. Falls back
# to a warning (and records it) if gh is unavailable or unauthenticated.
set_secret() {
  local name="$1" value="$2"
  if command -v gh >/dev/null 2>&1 && gh auth status >/dev/null 2>&1; then
    if printf '%s' "$value" | gh secret set "$name" >/dev/null 2>&1; then
      WRITTEN_SECRET+=("$name")
      printf '  %s✓ set%s GitHub secret %s\n' "$GREEN" "$RESET" "$name"
      return
    fi
  fi
  SKIPPED+=("GitHub secret $name (set it manually: gh secret set $name)")
  warn "skipped GitHub secret $name: gh not ready; set it later"
}

# set_var NAME VALUE sets a GitHub Actions repo variable (non-secret).
set_var() {
  local name="$1" value="$2"
  if command -v gh >/dev/null 2>&1 && gh auth status >/dev/null 2>&1; then
    if gh variable set "$name" --body "$value" >/dev/null 2>&1; then
      printf '  %s✓ set%s GitHub variable %s\n' "$GREEN" "$RESET" "$name"
      return
    fi
  fi
  SKIPPED+=("GitHub variable $name")
  warn "skipped GitHub variable $name, gh not ready; set it later"
}

# finish clears, then shows a closing summary of everything configured.
finish() {
  _clear
  printf '\n%s%s  ✓ Setup complete%s\n' "$BOLD" "$GREEN" "$RESET"
  (( ${#WRITTEN_ENV[@]} ))    && note "wrote ${#WRITTEN_ENV[@]} value(s) to $ENV_FILE: ${WRITTEN_ENV[*]}"
  (( ${#WRITTEN_SECRET[@]} )) && note "set ${#WRITTEN_SECRET[@]} GitHub secret(s): ${WRITTEN_SECRET[*]}"
  if (( ${#SKIPPED[@]} )); then
    printf '\n'; warn "still to do by hand:"
    for s in "${SKIPPED[@]}"; do note "  - $s"; done
  fi
  printf '\n'
}

# ──────────────────────────────────────────────────────────────────────────
# STAGES: author this section. One stage() per step the human takes.
# Replace the example below. Set TOTAL_STAGES to match the stages you write.
# Keep every stage inside run_wizard and the last line as is: bash then parses
# the whole file before the first prompt, so an edit mid-run can't break it.
# ──────────────────────────────────────────────────────────────────────────


run_wizard() {

TOTAL_STAGES=7
# Rien n'est enregistré : ni .env, ni secret GitHub. config.php s'écrit en phase 2.
ENV_FILE=/dev/null
DEPOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

banner "Quest & Love : environnement local (WAMP)"

stage "Prérequis : redistribuables Visual C++"
say "WampServer refuse de démarrer sans tous les redistribuables Visual C++ (2008 à 2022)."
open_url "https://wampserver.aviatechno.net/"
step "Section « Tools » : télécharger « Check VC++ packages » et le lancer."
step "S'il signale un manque : section « Visual C++ Redistributable Packages »,"
step "télécharger le paquet « all VC++ » et l'installer (droits administrateur)."
pause "Entrée quand l'outil de vérification ne signale plus rien."

stage "Installer WampServer 64 bits"
open_url "https://wampserver.aviatechno.net/"
step "Section « WampServer » : télécharger l'installateur complet 64 bits (version 3.3 ou plus)."
step "L'exécuter en administrateur ; garder le dossier proposé (C:\wamp64)."
step "Navigateur et éditeur par défaut : accepter les choix proposés."
pause "Entrée quand l'installation est terminée."
ask WAMP_DIR "Dossier d'installation de WAMP (ex. /c/wamp64) :"
[[ -d "$WAMP_DIR/bin/php" ]] || { warn "$WAMP_DIR/bin/php introuvable : vérifie le dossier puis relance."; exit 1; }

stage "Démarrer WAMP"
step "Lancer « Wampserver64 » depuis le menu Démarrer."
step "Attendre que l'icône W dans la zone de notification passe au VERT."
note "Orange = un service ne démarre pas (souvent le port 80 pris par Skype ou IIS)."
step "Clic gauche sur l'icône → MySQL ou MariaDB : repérer le SGBD qui affiche « Port used: 3306 »."
ask SGBD "SGBD sur le port 3306 (mysql ou mariadb) :"
[[ "$SGBD" == "mysql" || "$SGBD" == "mariadb" ]] || { warn "Réponse attendue : mysql ou mariadb."; exit 1; }
MYSQL_BIN=$(ls -d "$WAMP_DIR/bin/$SGBD"/*/bin 2>/dev/null | sort -V | tail -n1)
[[ -x "$MYSQL_BIN/mysql.exe" ]] || { warn "mysql.exe introuvable sous $WAMP_DIR/bin/$SGBD."; exit 1; }
note "Client SQL trouvé : $MYSQL_BIN"
pause

stage "Ajouter PHP au PATH utilisateur"
say "Versions fournies par WAMP : $(ls "$WAMP_DIR/bin/php" | grep '^php' | tr '
' ' ')"
note "Prendre la version la plus proche d'Azrael (doc technique § 6.4 : 8.2). 8.5.0 : Xdebug cassé chez WAMP."
ask PHP_CHOIX "Dossier PHP à utiliser (ex. php8.2.29) :"
PHP_DIR="$WAMP_DIR/bin/php/$PHP_CHOIX"
[[ -x "$PHP_DIR/php.exe" ]] || { warn "$PHP_DIR/php.exe introuvable."; exit 1; }
PHP_VERSION=$("$PHP_DIR/php.exe" -r 'echo PHP_VERSION;')
say "Version retenue : $PHP_VERSION ($PHP_DIR)"
"$PHP_DIR/php.exe" -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);' \
  || { warn "PHP 8.2 minimum requis : ajouter une version récente via les addons WAMP."; exit 1; }
PHP_DIR_WIN=$(cygpath -w "$PHP_DIR")
if confirm "Ajouter $PHP_DIR_WIN au PATH utilisateur ?"; then
  powershell.exe -NoProfile -Command "\$p=[Environment]::GetEnvironmentVariable('Path','User'); if (-not ((\$p -split ';') -contains '$PHP_DIR_WIN')) { [Environment]::SetEnvironmentVariable('Path', (\$p.TrimEnd(';') + ';$PHP_DIR_WIN'), 'User') }"
  printf '  %s✓%s PATH utilisateur mis à jour (effet dans les nouveaux terminaux, VS Code à relancer).\n' "$GREEN" "$RESET"
else
  SKIPPED+=("Ajouter $PHP_DIR_WIN au PATH utilisateur")
fi
step "Aligner Apache : clic gauche sur l'icône → PHP → Version → choisir $PHP_VERSION."
pause "Entrée quand l'icône est revenue au vert."

stage "Créer la base quest_love"
note "WAMP crée root sans mot de passe : laisser vide dans ce cas. Rien n'est enregistré."
ask_secret MYSQL_ROOT_MDP "Mot de passe root de $SGBD :"
export MYSQL_PWD="$MYSQL_ROOT_MDP"
"$MYSQL_BIN/mysql.exe" -u root -h 127.0.0.1 -P 3306 \
  -e "CREATE DATABASE IF NOT EXISTS quest_love CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
printf '  %s✓%s base quest_love prête (utf8mb4_unicode_ci).\n' "$GREEN" "$RESET"
pause

stage "VirtualHost quest-love.local"
# add_vhost refuse un dossier absent ; app/public naîtra vraiment en phase 2.
mkdir -p "$DEPOT/app/public"
RACINE_WIN=$(cygpath -w "$DEPOT/app/public")
open_url "http://localhost/add_vhost.php"
step "Nom du VirtualHost : quest-love.local"
step "Chemin complet du dossier : $RACINE_WIN"
step "Valider. WAMP écrit la configuration Apache et l'entrée du fichier hosts."
step "Puis clic droit sur l'icône → Outils → « Redémarrage DNS » (en anglais : Tools → Restart DNS)."
pause "Entrée quand l'icône est revenue au vert."

stage "Vérifications"
"$PHP_DIR/php.exe" --version | head -n1
"$PHP_DIR/php.exe" -r 'echo in_array("argon2id", password_algos(), true) ? "argon2id : disponible\n" : "argon2id : ABSENT\n";'
if "$MYSQL_BIN/mysql.exe" -u root -h 127.0.0.1 -P 3306 -N -e "SHOW DATABASES LIKE 'quest_love';" | grep -q quest_love; then
  say "base quest_love : présente"
else
  warn "base quest_love : introuvable"; SKIPPED+=("Créer la base quest_love")
fi
CODE=$(curl -s -o /dev/null -w '%{http_code}' http://quest-love.local/ || true)
if [[ "$CODE" != "000" ]]; then say "quest-love.local répond (HTTP $CODE)"; else warn "quest-love.local ne répond pas"; SKIPPED+=("VirtualHost quest-love.local"); fi
unset MYSQL_PWD
pause "Entrée pour le résumé."

finish
}

run_wizard "$@"; exit
