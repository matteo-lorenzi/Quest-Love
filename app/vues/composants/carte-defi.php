<?php
// Composant carte de défi — C1, C2 et C3 — lot 1 — commun : catégorie, titre, description (doc technique § 4.2) ; le statut arrivera avec C3.
// Reçoit : $defi (Defi).
?>
<article class="carte-defi">
    <p class="carte-defi__categorie"><?= e($defi->libelleCategorie) ?></p>
    <h2 class="carte-defi__titre"><?= e($defi->titre) ?></h2>
    <p class="carte-defi__description"><?= e($defi->description) ?></p>
</article>
