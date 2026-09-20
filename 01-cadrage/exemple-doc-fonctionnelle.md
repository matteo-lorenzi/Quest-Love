# Projet _Lean slideshow_

## Description synthétique

Auteur : Yannis Delmas <yannis.delmas@univ-poitiers.fr>

Pitch :

> L’application web _Lean slideshow_ permet de créer des diaporamas interactifs à partir de fichiers Markdown.
Simple, rapide et ultra-léger. Les diapositives peuvent contenir du texte, des images, des vidéos, des exemples
de code, des diagrammes et même des équations mathématiques.
>
> Les diaporamas et leurs fichiers associés peuvent être enregistrés sous forme de fichiers ZIP et partagés
facilement avec d’autres utilisateurs.

Application : <https://azrael.sha.univ-poitiers.fr/~ydelmas/26-27-m2-programmation/projet-yannis/application/>

Code source : <https://azrael.sha.univ-poitiers.fr/~ydelmas/26-27-m2-programmation/debug.php/projet-yannis/application/>

## Principales caractéristiques

### _Minimum viable product_{lang=en} (MVP)

- Une page statique HTML+CSS+JS permettant de visualiser un diaporama à partir d’un fichier Markdown
  - Le fichier Markdown peut être seul (standalone) ou bien dans un fichier ZIP avec ses fichiers associés (images…)
  - Navigation d’une projection du diaporama à l’aide de liens hypertextes et/ou au clavier (JavaScript)
- Mini-application PHP
  - Permet de télécharger la page statique de présentation (trois fichiers (HTML, CSS, JS) ou bien un seul fichier HTML)
  - Permet de composer un thème à intégrer à un diaporama Markdown
  - Les thèmes peuvent être sauvegardés/restaurés et importés/exportés

### Scénario d’utilisation

1. L’utilisateur **auteur** rédige un **diaporama** (slideshow) en Markdown
2. L’application en ligne permet à l’auteur de créer un **thème** qui peut être intégré aux métadonnées du diaporama
3. L’application permet de gérer des thèmes : sauvegarde/restauration, modification, importation/exportation (Yaml ou Markdown)
4. L’auteur peut alors intégrer ce thème à son diaporama (dans les métadonnées)
5. Le diaporama peut enfin être présenté au moyen d’un **visualiseur**, page HTML statique téléchargeable depuis l’application
    - L’auteur charge le diaporama à l’aide d’un champ de type fichier
    - _(lot 1)_ Ce fichier est au format Markdown (voir ci-dessous)
    - _(lot 2)_ Ce fichier peut aussi être une archive ZIP contenant le diaporama proprement dit et ses images
6. _(lot 3)_ L’auteur peut exporter le diaporama en HTML, pour visualisation ou pour impression

### Guide utilisateur pour la création de diaporamas (trame)

- Diapositives simples :
  - Titre de niveau identique, 2, 3 ou 4 : `#### Exemple de titre`{.language-markdown}
  - 1 à 4 blocs Markdown : texte, image, vidéo, code, diagramme…
    - Le document Markdown sera traduit par `markdown-it`, y compris
      diagrammes Mermaid et formules MathJax
    - On peut indiquer une légende pour les images : `![texte alternatif](chemin-image "Légende")`{.language-markdown}
  - Les blocs sont disposés automatiquement (CSS), selon le nombre de blocs
    - Classes `.block-1`, `.block-2`, `.block-3` pour imposer le numéro d’ordre d’un bloc dans la diapo
    - Classe `.block-main` pour désigner le bloc principal d’une diapo à trois blocs
      (celui qui est plus grand que les autres)
- Diapositives de titre :
  - Titre de niveau inférieur à celui des diapositives simples
  - Titre principal = niveau 1 : `# Titre principal`{.language-markdown}
  - Titre de niveau 2 pour les chapitres du diaporama : `## Titre de niveau 2`{.language-markdown}
    - Obligatoirement après le titre de niveau 1
  - Titre de niveau 3 pour les sous-chapitres : `### Titre de niveau 3`{.language-markdown}
    - Obligatoirement après un titre de niveau 2
  - Tous les blocs jusqu’au prochain titre sont ajoutés à cette diapositive
- On peut créer un lien vers une diapositive : `[Texte du lien](#identifiant)`{.language-markdown}
  - L’identifiant peut être spécifié dans le titre d’une diapositive : `## Titre diapo {#identifiant}`{.language-markdown}
  - Sinon, un identifiant est créé automatiquement : `slide-NN` (NN = numéro de diapositive)
- Le diaporama peut comporter des métadonnées (format Yaml), au début du fichier Markdown

    ```markdown
    ---
    author: Yannis Delmas
    updated: 2026-09-15
    footer:
      text: Université de Poitiers
      date: M2 Programmation
      logo: logo-UP.png
    theme:
      level-1:
        marker-type: "◆"
        marker-color: rgb(102 51 0)
    ---

    # Titre principal
    …
    ```

  - Le thème du diaporama peut être créé à partir de l’application compagnon en ligne
- Le diaporama peut être enregistré en deux formats :
  - Fichier Markdown seul (standalone)
  - _(lot 2)_ Fichier ZIP, contenant :
    - Diaporama en Markdown : `index.md`
    - Thème et éventuelles autres métadonnées (optionnel) : `metadata.yaml`
    - Images ou autres ressources associées, à la racine ou dans des sous-dossiers

### Documentation détaillée des métadonnées de diaporama

- `author` : nom de l’auteur, inséré dans les métadonnées HTML (lot 3)
- `updated` : date de dernière mise à jour, insérée dans les métadonnées HTML (lot 3)
- `footer` : indications de pied des diapositives (masque)
  - `text` : indication principale, p. ex. titre court de la présentation
  - `date` : indication secondaire, p. ex. nom de l’auteur ou de l’institution, date de la présentation
  - `logo` : chemin vers le logo de l’institution, affiché dans le masque
- `theme` : thème du diaporama
  - `color-primary` : couleur principale du thème
    - N’importe quelle valeur utilisable avec la propriété CSS `color` : nom de couleur (`maroon`), code RGB (`rgb(102 51 0)`), code hexadécimal (`#663300`)…
    - Définit la variable CSS `--color-primary`
  - `color-secondary` : couleur secondaire du thème, définit la variable CSS `--color-secondary`
  - `color-text` : couleur d’écriture générale du document, définit la variable CSS `--color-text`
  - `color-heading` : couleur d’écriture des titres, par défaut `var(--color-primary)`
  - `background` : couleur ou n’importe quelle autre valeur utilisable avec la propriété CSS `background`
    - Ceci peut comporter une image de fond,
      p. ex. `url(fond.svg) no-repeat center/cover`
    - Et/ou un filet au dessus du pied de page,
      p. ex. `linear-gradient(to top, transparent 0, transparent 3lh, var(--color-primary) 3lh, var(--color-primary) 3.5lh,transparent 3.5lh);`
  - `font-text` : police générale du document
    - Famille de polices ou valeur plus complexe utilisable avec la propriété CSS `font`
  - `font-heading` : police des titres
    - Famille de polices ou valeur plus complexe utilisable avec la propriété CSS `font`
  - `heading-2-before`, `heading-2-after` : contenu avant et après les titres de chapitre : fleurons ou compteur…
    - N’importe quelle valeur utilisable avec la propriété CSS `content` de `::before`/`::after`
    - Le compteur `h2` est disponible pour les chapitres
    - Exemple : `"🙠 " counter(h2) " 🙢"`
  - `heading-3-before`, `heading-3-after` : contenu avant et après les titres de sous-chapitre
    - Le compteur `h3` est disponible pour les sous-chapitres
    - Exemple : `"– " counter(h2) "." counter(h3) " –"`
  - `level-1` : style des listes de niveau 1
    - `marker-type` : type de puce, de numérotation ou de fleuron
      - Mot-clé utilisable avec la propriété CSS `list-style-type` : `disc`, `circle`, `square`, `decimal`…
      - Ou caractère Unicode entre guillemets : `"◆"`, `"★"`, `"☀"`…
      - Ou n’importe quelle valeur utilisable avec la propriété CSS `content` de `::marker`
        ([documentation MDN](https://developer.mozilla.org/fr/docs/Web/CSS/Reference/Properties/content#syntaxe))
    - `marker-color` : couleur de ce marqueur
      - N’importe quelle valeur utilisable avec la propriété CSS `color`, par défaut `var(--color-primary)`
  - `level-2` : styles des listes de niveau 2 ; couleur par défaut du marqueur : `var(--color-secondary)`
  - `level-3` : styles des listes de niveau 3 ; couleur par défaut du marqueur : `var(--color-text)`

## Description fonctionnelle

### Maquette d’une diapositive simple

![Maquette d’une diapositive simple](maquette-diapo.png)

Les diapositives de titre sont similaires : la zone d’affichage regroupe celles de titre et celle de contenu.

### Maquette de l’application en ligne

Maquette pour la création d’un thème de diaporama :

![Maquette de l’application](maquette-theme.png)

### Lots

#### Lot 1 : diaporama Markdown

- [ ] Fichier Markdown d’exemple pour les tests : `exemple.md`
- [ ] Fichier HTML correspondant à cet exemple : `exemple.html`, avec son CSS
- [ ] Visualiseur de diaporama : `visualiseur.html`, `visualiseur.css`, `visualiseur.js`
  - [ ] Sélection d’un fichier Markdown
  - [ ] Transformation du Markdown en HTML (avec `markdown-it`)
  - [ ] Visualisation du diaporama, navigation par hyperliens et clavier
- [ ] Application compagnon en ligne : `lean-slideshow.php`
  - [ ] Création/modification d’un thème
  - [ ] Gestion des thèmes : sauvegarde/restauration, import/copie/télécharger
  - [ ] Hyperlien vers le visualiseur
  - [ ] Téléchargement du visualiseur en un seul fichier HTML

#### Lot 2 : diaporama ZIP

- [ ] Le diaporama peut être fourni sous la forme d’un fichier ZIP
  - [ ] Les images intégrées au ZIP sont traduites en URL internes au navigateur
  - [ ] Les métadonnées du diaporama sont dans un fichier `metadata.yaml` (optionnel)

#### Lot 3 : exportation du diaporama

- [ ] Exportation du diaporama en HTML (CSS et JS inclus)
- [ ] Exportation du diaporama en HTML destiné à l’impression en PDF (CSS pour impression)

## Idées pour la suite

- Interaction de la présentation
  - Diapositive visualisée : `:target`, précédentes : `.past`, suivantes : `.future`
  - Les `.past` sont placées à gauche, la `:target` au centre, les `.future` à droite ; `transition` pour les faire glisser
  - Contrôles suivante / précédente à l’aide d’hyperliens ; JS en plus pour faire au clavier
  - JS pour gérer les affectations de `.past` et `.future` à chaque changement de diapositive
- Le diaporama est produit en JS
  - Une diapositive est créée pour chaque titre
    - Diapositive simple : élément `<section>`
    - Diapositive de titre : éléments `<header>` et on groupe les diapositives concernées dans une `<section>`
  - Si un bloc comporte seulement une `<img>`, on crée un élément `<figure>`
    - _Markdown-it_ place les images dans un `<p>` par défaut, à transformer en `<figure>`
    - S’il y a un attribut `title`, on l’utilise pour créer un `<figcaption>`
    - Note : Les diagrammes Mermaid sont déjà placés dans un bloc `<pre>`. Il n’y a rien à faire, dans ce cas.
  - Si le titre porte un `#id`, il est déplacé sur la diapositive correspondante ; sinon, `#slide-NN` est ajouté.
  - L’attribut `data-slide` avec un numéro de diapositive est ajouté
    - CSS : `[data-slide]`, `header[data-slide]`, `section[data-slide]` pour cibler les diapos
    - CSS : affichage du numéro de diapo avec `content: attr(data-slide)`
    - Compteurs CSS :
      ```css
      :root {counter-reset: h2; }
      h2 {counter-increment: h2; counter-reset: h3; }
      h3 {counter-increment: h3; }
      ```
- Lot 2 : fichier ZIP pour stocker le diaporama et ses images
  - Les métadonnées de `index.md` sont prioritaires sur celles de `metadata.yaml`
  - Bibliothèque JS pour lire les fichiers ZIP côté client : [JSZip](https://stuk.github.io/jszip/)
