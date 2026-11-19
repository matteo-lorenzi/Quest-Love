<?php
// Service de rattrapage paresseux — tous écrans connectés, D1, D3, C1 état 5, C1 journée blanche « l'un de vous n'a pas choisi » — lot 1 — B. Rejoue ce qui aurait dû se produire depuis la dernière visite (doc technique § 3.2).

class RattrapageService
{
    // Le service reçoit la connexion : le rattrapage est une transaction, et les transactions vivent dans les services (ADR-0001).
    public function __construct(private PDO $pdo, private JoueurDepot $joueurs, private MissionDepot $missions, private JourneeService $journee, private MissionService $missionService) {}

    // Le routeur et les tests construisent le service de la même façon, comme AppairageService.
    public static function construire(PDO $pdo): self
    {
        $missions = new MissionDepot($pdo);
        return new self($pdo, new JoueurDepot($pdo), $missions, new JourneeService(new ParticipationDepot($pdo), new DuoDepot($pdo), $missions), MissionService::construire($pdo));
    }

    // Appelé par le routeur une fois par requête, avant toute sortie. Rend les événements à montrer, dans l'ordre de la file :
    // duo dissous, expiration, passage à zéro (§ 3.2). Le routeur les range en session : le service ne touche pas à la session.
    // Une transaction qui verrouille les deux lignes joueur du duo : deux appareils qui chargent en même temps ne rejouent rien deux fois.
    // Sans rien à rejouer (ni marqueur, ni mission non révélée dans le duo, ni mission reçue échue), deux lectures indexées et aucun verrou : la page n'est pas ralentie (spec #59, récit 9).
    /** @return Evenement[] */
    public function rattraper(Joueur $joueur, DateTimeImmutable $maintenant): array
    {
        // Sortie rapide hors verrou : $joueur vient d'être relu par le routeur. Un marqueur posé entre-temps sera vu à la requête suivante.
        // Révélation et annulation exigent une mission non révélée dans le duo, reçue ou émise : celui qui avait choisi doit voir l'annulation à 12 h.
        // L'expiration exige une mission en cours reçue par le joueur, dont minuit est passé.
        if (!$joueur->dissolutionASignaler && ($joueur->duoId === null
            || ($this->missions->trouverParDuoEtStatut($joueur->duoId, StatutMission::AttribueNonRevele) === [] && $this->echues($joueur->id, $maintenant) === []))) {
            return [];
        }
        $evenements = [];
        $this->pdo->beginTransaction();
        try {
            // Les deux lignes verrouillées en une fois, décision prise sur les lignes fraîches. Duo changé entre-temps : rien n'est rejoué, la requête suivante le fera.
            [$frais, $partenaire] = $this->joueurs->verrouillerAvecPartenaire($joueur->id) ?? [null, null];
            if ($frais !== null) {
                // D3 : le marqueur est lu puis remis à 0 sous verrou, donc signalé une seule fois.
                if ($frais->dissolutionASignaler) {
                    $this->joueurs->effacerDissolutionASignaler($frais->id);
                    $evenements[] = Evenement::DuoDissous;
                }
                if ($partenaire !== null) {
                    $this->reveler($frais, $partenaire, $maintenant);
                    $this->annuler($frais->duoId, $maintenant);
                    // Expiration après la révélation (revue de #59) : une révélation constatée en retard expire dans le même rattrapage.
                    array_push($evenements, ...$this->expirer($frais, $maintenant));
                }
            }
            $this->pdo->commit();
        } catch (Throwable $erreur) {
            $this->pdo->rollBack();
            throw $erreur;
        }
        return $evenements;
    }

    // Révélation (doc technique § 3.4) : dès que les deux ont choisi pour la même journée, les deux missions passent en cours, sans repli à 12 h.
    // Relue sous verrou : un second rattrapage ne trouve plus de mission non révélée et ne révèle rien.
    // La date limite est le minuit qui suit le choix, par le calendrier : une révélation rattrapée le lendemain ne prolonge pas le défi.
    private function reveler(Joueur $joueur, Joueur $partenaire, DateTimeImmutable $maintenant): void
    {
        $pourLePartenaire = [];
        foreach ($this->missions->trouverParDestinataireEtStatut($partenaire->id, StatutMission::AttribueNonRevele) as $mission) {
            $pourLePartenaire[$this->journee->journee($mission->dateAttribution)] = $mission;
        }
        foreach ($this->missions->trouverParDestinataireEtStatut($joueur->id, StatutMission::AttribueNonRevele) as $mission) {
            $autre = $pourLePartenaire[$this->journee->journee($mission->dateAttribution)] ?? null;
            if ($autre !== null) {
                $minuit = $this->journee->minuitSuivant($mission->dateAttribution);
                $this->missions->reveler($mission->id, $maintenant, $minuit);
                $this->missions->reveler($autre->id, $maintenant, $minuit);
            }
        }
    }

    // Expiration (doc technique § 3.4), dans l'ordre chronologique : seules les missions reçues par le joueur qui charge la page,
    // parce que D1 va dans sa file de session ; celles de son partenaire expireront à son propre rattrapage. Un D1 par mission expirée.
    /** @return Evenement[] */
    private function expirer(Joueur $joueur, DateTimeImmutable $maintenant): array
    {
        $evenements = [];
        foreach ($this->echues($joueur->id, $maintenant) as $mission) {
            if ($this->missionService->expirer($mission, $maintenant)) {
                $evenements[] = $mission->dateDecouverte === null ? Evenement::DefiExpireJamaisDecouvert : Evenement::DefiExpire;
            }
        }
        return $evenements;
    }

    // Missions en cours reçues par le joueur dont minuit est passé, de la plus ancienne à la plus récente (index ix_mission_rattrapage).
    /** @return Mission[] */
    private function echues(int $joueurId, DateTimeImmutable $maintenant): array
    {
        return array_values(array_filter(
            $this->missions->trouverParDestinataireEtStatut($joueurId, StatutMission::EnCours),
            fn (Mission $mission) => $maintenant >= $mission->dateLimite,
        ));
    }

    // Annulation (doc technique § 3.4) : après la révélation, une mission encore non révélée à la bascule de sa journée n'a pas eu de réponse.
    // Statut seul : aucun PV perdu, et sans date_revelation le défi n'est pas compté comme reçu (glossaire, Annulation). Relue sous verrou : annulée une seule fois.
    private function annuler(int $duoId, DateTimeImmutable $maintenant): void
    {
        foreach ($this->missions->trouverParDuoEtStatut($duoId, StatutMission::AttribueNonRevele) as $mission) {
            if ($maintenant >= $this->journee->bascule($mission->dateAttribution)) {
                $this->missions->changerStatut($mission->id, StatutMission::Annule);
            }
        }
    }
}
