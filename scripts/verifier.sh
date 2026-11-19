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
# Seul le jeu de démonstration date ses lignes du jour du rechargement (sql/demonstration.sql, TODO(simplif)).
sql_jour=$(grep -niE 'CURDATE\(|CURRENT_DATE' -- "${fichiers[@]}" /dev/null | grep -v '^sql/demonstration.sql:')
[ -n "$sql_jour" ] && signaler "date SQL du jour utilisée hors du jeu de démonstration :"$'\n'"$sql_jour"

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

# 6. La couche modèle ne connaît ni les écrans ni les adresses (ADR-0001) : une requête « ecran=… » se construit dans un contrôleur ou le routeur.
adresses=$(grep -nE 'ecran=' -- $(printf '%s\n' "${fichiers[@]}" | grep '^app/modeles/.*\.php$') /dev/null)
[ -n "$adresses" ] && signaler "adresse d'écran construite dans app/modeles/ (ADR-0001) :"$'\n'"$adresses"

# 7. Redirection après écriture (§ 2.4) : la méthode POST de chaque écran de la table du routeur ne rend jamais de vue.
#    Ne lit que le corps de la méthode : une fonction privée qui rend est attrapée à l'exécution par le routeur.
rendus=$(grep -oE "\[\s*'[A-Za-z]+Controleur', ('[a-zA-Z]+'|null), '[a-zA-Z]+'" app/noyau/Routeur.php \
    | sed -E "s/\[ *'([A-Za-z]+)', [^,]+, '([a-zA-Z]+)'/\1 \2/" | sort -u \
    | while read -r classe methode; do
        awk -v m="$methode" -v f="app/controleurs/$classe.php" '
            $0 ~ "function " m "\\(" { dedans = 1; next }
            dedans && /^    }/ { exit }
            dedans && /rendre\(/ { print f ":" FNR ": " $0 }' "app/controleurs/$classe.php"
    done)
[ -n "$rendus" ] && signaler "vue rendue par une méthode POST au lieu de rediriger (§ 2.4) :"$'\n'"$rendus"

# 8. Un service verrouille les deux joueurs d'un duo en une fois (JoueurDepot::verrouillerAvecPartenaire) : verrouiller un joueur seul,
#    puis son partenaire, interbloque deux joueurs du duo qui le font en même temps (MySQL 1213, revue de la spec #59).
seuls=$(grep -nE 'verrouiller\(\[[^],]*\]\)' -- $(printf '%s\n' "${fichiers[@]}" | grep '^app/modeles/services/.*\.php$') /dev/null)
[ -n "$seuls" ] && signaler "joueur verrouillé seul dans un service (utiliser verrouillerAvecPartenaire) :"$'\n'"$seuls"

[ "$echec" -eq 0 ] && echo "Vérifications : tout est conforme."
exit "$echec"
