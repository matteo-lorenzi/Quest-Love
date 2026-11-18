<?php
// Service du tirage — C2 Tirage, étape « éventail » — parcours 2 (journée de jeu) — lot 1 — B. Trois propositions par émetteur et par journée (doc technique § 3.3).

class TirageService
{
    // Les dépôts et la journée sont construits par l'appelant, contrôleur ou test (ADR-0001). La connexion sert à la transaction.
    public function __construct(
        private PDO $pdo,
        private JourneeService $journee,
        private TirageDepot $tirages,
        private DefiDepot $defis,
        private JoueurDepot $joueurs,
    ) {}

    // Tirage du jour de l'émetteur : créé à sa première demande, relu ensuite, pour qu'un rechargement ne redonne pas d'autres défis.
    public function tirer(Joueur $emetteur, DateTimeImmutable $maintenant): ?Tirage
    {
        $jour = JourneeService::enHeureDeParis($maintenant);
        $existant = $this->tirages->trouverParEmetteurEtJour($emetteur->id, $jour);
        if ($existant !== null) {
            return $existant;
        }
        // Jamais avant les deux oui (état 3) ; jamais après 12 h non plus, quand aucun choix ne serait plus accepté.
        if ($this->journee->etatAccueil($emetteur, $maintenant) !== EtatAccueil::APiocher || !$this->journee->avantBascule($maintenant)) {
            return null;
        }
        $destinataire = $this->joueurs->trouverPartenaire($emetteur);
        // Sous transaction : un tirage n'existe jamais sans ses trois propositions. Une demande concurrente déjà passée fait renvoyer null à creer :
        // rien n'est écrit, et la relecture rend le tirage de l'autre demande (clé unique, modèle de données § 4).
        // TODO(simplif): réservoir = toute la bibliothèque, sans exclusion des défis reçus ni des trois jours, à remplacer par les règles de la #72.
        $this->pdo->beginTransaction();
        try {
            $this->tirages->creer($emetteur->duoId, $emetteur->id, $destinataire->id, $jour, array_rand($this->defis->tous(), 3));
            $this->pdo->commit();
        } catch (Throwable $erreur) {
            $this->pdo->rollBack();
            throw $erreur;
        }
        return $this->tirages->trouverParEmetteurEtJour($emetteur->id, $jour);
    }
}
