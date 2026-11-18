<?php
// Service du tirage — C2 Tirage, étapes « éventail » et « carte piochée » — parcours 2 (journée de jeu) — lot 1 — B. Trois propositions par émetteur et par journée, puis le choix définitif (doc technique § 3.3).

class TirageService
{
    // Les dépôts et la journée sont construits par l'appelant, contrôleur ou test (ADR-0001). La connexion sert à la transaction.
    public function __construct(
        private PDO $pdo,
        private JourneeService $journee,
        private TirageDepot $tirages,
        private DefiDepot $defis,
        private JoueurDepot $joueurs,
        private MissionDepot $missions,
    ) {}

    // Tirage du jour de l'émetteur : créé à sa première demande, relu ensuite, pour qu'un rechargement ne redonne pas d'autres défis.
    // Sans éventail à montrer, l'état de l'accueil : hors état 3 (pas encore les deux oui, défi déjà envoyé), ou état 3 passé 12 h, quand le choix n'est plus possible.
    public function tirer(Joueur $emetteur, DateTimeImmutable $maintenant): Tirage|EtatAccueil
    {
        $etat = $this->journee->etatAccueil($emetteur, $maintenant);
        if ($etat !== EtatAccueil::ATirer || !$this->journee->avantBascule($maintenant)) {
            return $etat;
        }
        $jour = $this->journee->enHeureDeParis($maintenant);
        $existant = $this->tirages->trouverParEmetteurEtJour($emetteur->id, $jour);
        if ($existant !== null) {
            return $existant;
        }
        $destinataire = $this->joueurs->trouverPartenaire($emetteur);
        // Sous transaction : un tirage n'existe jamais sans ses trois propositions. Une demande concurrente déjà passée fait renvoyer null à creer :
        // rien n'est écrit, et la relecture rend le tirage de l'autre demande (clé unique, modèle de données § 4).
        $this->pdo->beginTransaction();
        try {
            $this->tirages->creer($emetteur->duoId, $emetteur->id, $destinataire->id, $jour, $this->proposer($destinataire->id, $jour));
            $this->pdo->commit();
        } catch (Throwable $erreur) {
            $this->pdo->rollBack();
            throw $erreur;
        }
        return $this->tirages->trouverParEmetteurEtJour($emetteur->id, $jour);
    }

    // Réservoir du destinataire (doc technique § 3.3) : les défis qu'il n'a jamais reçus, hors propositions non retenues depuis moins de trois jours.
    /** @return int[] trois numéros de défi distincts */
    private function proposer(int $destinataireId, DateTimeImmutable $jour): array
    {
        $bibliotheque = $this->defis->tous();
        $recents = array_flip($this->tirages->nonRetenusApres($destinataireId, $jour->modify('-3 days')));
        // Le cycle n'est pas stocké (modèle de données, sans debut_cycle) : il se rejoue sur les défis reçus, dans l'ordre, en repartant de zéro
        // chaque fois qu'il ne laissait plus trois défis. Sans ce rejeu, chaque tirage après le premier épuisement repartirait de zéro.
        // TODO(simplif): le rejeu ignore la fenêtre de trois jours et suppose la bibliothèque inchangée, à revoir si une remise à zéro tombe à contretemps.
        $cycle = [];
        foreach ($this->missions->recusPar($destinataireId) as $defiId) {
            if (count(array_diff_key($bibliotheque, $cycle)) < 3) {
                $cycle = [];
            }
            $cycle[$defiId] = true;
        }
        $disponibles = array_diff_key($bibliotheque, $cycle, $recents);
        // Sous trois défis, le cycle repart à zéro : les défis reçus redeviennent disponibles.
        if (count($disponibles) < 3) {
            $disponibles = array_diff_key($bibliotheque, $recents);
        }
        // La fenêtre n'écarte que les propositions des deux dernières journées : elle ne cède à son tour que pour une bibliothèque minuscule (tests).
        if (count($disponibles) < 3) {
            $disponibles = $bibliotheque;
        }
        // array_rand rend les numéros dans l'ordre de la bibliothèque : mélangés, la place d'une carte ne dit rien de son défi.
        $tires = array_rand($disponibles, 3);
        shuffle($tires);
        return $tires;
    }

    // Choix du défi du partenaire parmi les trois propositions du tirage du jour : crée sa mission, non révélée (statut attribue_non_revele).
    // Faux si le choix est refusé, rien n'étant écrit : défi hors des propositions (formulaire falsifié), ou choix arrivé à 12 h ou après (horodatage serveur).
    // Un second choix (double clic, second appareil, envois simultanés) n'est pas relu avant l'écriture : la clé unique tirage_id le rejette, le premier reste définitif.
    public function choisir(Joueur $emetteur, int $defiId, DateTimeImmutable $maintenant): bool
    {
        $aParis = $this->journee->enHeureDeParis($maintenant);
        $tirage = $this->tirages->trouverParEmetteurEtJour($emetteur->id, $aParis);
        if ($tirage === null || !in_array($defiId, $tirage->defiIds, true)) {
            return false;
        }
        if (!$this->journee->avantBascule($maintenant)) {
            // Celui qui avait déjà choisi (page restée ouverte) n'est pas refusé : son choix a été pris en compte.
            return $this->missions->trouverParTirage($tirage->id) !== null;
        }
        $this->missions->ajouter($tirage, $defiId, $aParis, $this->journee->minuitSuivant($maintenant));
        return true;
    }
}
