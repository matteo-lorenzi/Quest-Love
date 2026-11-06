#!/usr/bin/env bash
# Vérifications mécaniques du doc technique (§ 2.4, § 3.8) sur app/ et sql/ — lancées par la CI, et en local : bash scripts/verifier.sh

cd "$(git rev-parse --show-toplevel)" || exit 1
echec=0
signaler() { printf 'ÉCHEC : %s\n' "$1"; echec=1; }

# Fichiers suivis ou nouveaux non ignorés : config.php, local, reste hors du contrôle.
mapfile -t fichiers < <(git ls-files --cached --others --exclude-standard app sql)

# 1. Syntaxe PHP.
for f in "${fichiers[@]}"; do
    [[ "$f" == *.php ]] || continue
    php -l "$f" > /dev/null || signaler "syntaxe PHP : $f"
done

# 2. Seul public/index.php lit l'horloge (§ 2.4) : les autres reçoivent $maintenant.
lectures=$(grep -nE 'new DateTime(Immutable)?\(|\btime\(\)|\bdate\(|strtotime\(|microtime\(' -- $(printf '%s\n' "${fichiers[@]}" | grep '\.php$') /dev/null \
    | grep -v '^app/public/index.php:')
[ -n "$lectures" ] && signaler "horloge lue hors de app/public/index.php :"$'\n'"$lectures"

# 3. Horodatages écrits depuis $maintenant, jamais par l'horloge SQL (§ 3.8).
sql_horloge=$(grep -niE 'NOW\(\)|CURRENT_TIMESTAMP' -- "${fichiers[@]}" /dev/null)
[ -n "$sql_horloge" ] && signaler "horloge SQL utilisée :"$'\n'"$sql_horloge"

# 4. Chaque fichier commence par sa ligne rôle / écran / lot / périmètre (§ 2.4).
for f in "${fichiers[@]}"; do
    l1=$(sed -n 1p "$f"); l2=$(sed -n 2p "$f")
    case "$f" in
        *.php) [[ "$l1" =~ ^\<\?php\ //\  || ( "$l1" == '<?php' && "$l2" =~ ^//\  ) ]] ;;
        *.sql) [[ "$l1" =~ ^--\  ]] ;;
        *.css | *.js) [[ "$l1" =~ ^/[*/] ]] ;;
        *) [[ "$l1" =~ ^#\  ]] ;;
    esac || signaler "ligne d'en-tête absente : $f"
done

# 5. Motif d'expression rationnelle terminé par $ : sans le modificateur D, $ accepte aussi un saut de ligne final
#    (« Nina\n » passait un contrôle d'identifiant). Écrire \z à la place, ou ajouter D. Ne voit que les motifs écrits en littéral.
motif_dollar=$'preg_[a-z_]+\\([[:space:]]*[\'"]/.*[^\\\\]\\$/[a-zA-Z]*[\'"]'
avec_d=$'\\$/[a-zA-Z]*D[a-zA-Z]*[\'"]'
dollars=$(grep -nE "$motif_dollar" -- $(printf '%s\n' "${fichiers[@]}" | grep '\.php$') /dev/null | grep -vE "$avec_d")
[ -n "$dollars" ] && signaler "motif terminé par \$ sans modificateur D (utiliser \\z) :"$'\n'"$dollars"

[ "$echec" -eq 0 ] && echo "Vérifications : tout est conforme."
exit "$echec"
