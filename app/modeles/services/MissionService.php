<?php
// Service des missions — C3 Défi reçu — parcours 2 (journée de jeu) — lot 1 — B. Découverte et validation (doc technique § 3.4) ; révélation et annulation sont au rattrapage.

class MissionService
{
    // La connexion sert à la transaction de la validation ; les dépôts sont construits par l'appelant (ADR-0001).
    public function __construct(private PDO $pdo, private JoueurDepot $joueurs, private MissionDepot $missions) {}

    // Le contrôleur et les tests construisent le service de la même façon, comme RattrapageService.
    public static function construire(PDO $pdo): self
    {
        return new self($pdo, new JoueurDepot($pdo), new MissionDepot($pdo));
    }

    // Défi reçu du jour, révélé, en cours ou réalisé : null sinon (rien à montrer sur C3). Seul ce GET de C3 pose la découverte, jamais l'annonce de C1 (spec #60, récit 9).
    // Le dépôt n'écrit que si la découverte est encore nulle : un second affichage ne la déplace pas.
    public function decouvrir(Joueur $destinataire, DateTimeImmutable $maintenant): ?Mission
    {
        $mission = $this->recue($destinataire, $maintenant);
        if ($mission !== null) {
            $this->missions->decouvrir($mission->id, $maintenant);
        }
        return $mission;
    }

    // Déclaration « réalisé » (cadrage § 4.3), définitive. Refusée sans rien écrire si la mission n'est pas en cours (déjà validée, expirée, annulée),
    // si le joueur n'en est pas le destinataire dans son duo actuel, ou passé minuit (page restée ouverte).
    // Sous transaction, les deux joueurs du duo verrouillés comme au rattrapage : le statut est relu frais, donc deux appareils ne valident qu'une fois
    // et une expiration concurrente (#61) ne passe pas entre la lecture et l'écriture.
    public function valider(Joueur $destinataire, int $missionId, DateTimeImmutable $maintenant): void
    {
        $this->pdo->beginTransaction();
        try {
            // Le joueur relu sous verrou (le partenaire n'est verrouillé que pour sérialiser le duo) ; null si son duo a changé entre-temps.
            $frais = $this->joueurs->verrouillerAvecPartenaire($destinataire->id)[0] ?? null;
            $mission = $this->missions->trouverParId($missionId);
            $validable = $frais !== null && $mission !== null
                && $mission->destinataireId === $frais->id && $mission->duoId === $frais->duoId
                && $mission->statut === StatutMission::EnCours
                && $maintenant < $mission->dateLimite;
            if ($validable) {
                $this->missions->valider($mission->id, $maintenant);
            }
            $this->pdo->commit();
        } catch (Throwable $erreur) {
            $this->pdo->rollBack();
            throw $erreur;
        }
    }

    // Le duo du joueur borne la lecture : seules les missions du jour de son duo, dont il est le destinataire.
    private function recue(Joueur $destinataire, DateTimeImmutable $maintenant): ?Mission
    {
        if ($destinataire->duoId === null) {
            return null;
        }
        [$recue] = $this->missions->trouverParJoueurEtJour($destinataire->duoId, $destinataire->id, JourneeService::enHeureDeParis($maintenant));
        return in_array($recue?->statut, [StatutMission::EnCours, StatutMission::Valide], true) ? $recue : null;
    }
}
