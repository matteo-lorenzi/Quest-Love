<?php // Gabarit de pied — tous écrans — lot 1 — commun. ?>
<?php if (HORLOGE_DECALAGE !== ''): ?>
    <footer class="pied">
        <!-- Visible pour qu'une démonstration ne soit jamais confondue avec l'heure réelle (doc technique § 3.8). -->
        <p class="pied__horloge">[TEXTE À RÉDIGER : horloge de démonstration active] (<?= e(HORLOGE_DECALAGE) ?>)</p>
    </footer>
<?php endif ?>
</body>
</html>
