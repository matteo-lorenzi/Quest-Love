<?php
// Service de rattrapage paresseux — tous écrans connectés, D3 — lot 1 — B. Rejoue ce qui aurait dû se produire depuis la dernière visite (doc technique § 3.2).

class RattrapageService
{
    // Le service reçoit la connexion : le rattrapage est une transaction, et les transactions vivent dans les services (ADR-0001).
    public function __construct(private PDO $pdo, private JoueurDepot $joueurs) {}

    // Appelé par le routeur une fois par requête, avant toute sortie. Rend les événements à montrer, dans l'ordre de la file :
    // duo dissous, expiration, passage à zéro (§ 3.2). Le routeur les range en session : le service ne touche pas à la session.
    // Une transaction qui verrouille les deux lignes joueur du duo : deux appareils qui chargent en même temps ne rejouent rien deux fois.
    // Sans rien à rejouer, deux lectures par clé primaire : la page n'est pas ralentie.
    // TODO(simplif): seul D3 est rejoué, expiration, révélation et annulation arrivent avec leurs tickets (spec #59, #61).
    /** @return Evenement[] */
    public function rattraper(Joueur $joueur, DateTimeImmutable $maintenant): array
    {
        $evenements = [];
        $this->pdo->beginTransaction();
        try {
            // Même ordre de verrou que la dissolution : le joueur seul, puis les deux, décision prise sur les lignes fraîches.
            $frais = $this->joueurs->verrouiller([$joueur->id])[$joueur->id] ?? null;
            if ($frais !== null) {
                $partenaire = $frais->duoId === null ? null : $this->joueurs->trouverPartenaire($frais);
                $this->joueurs->verrouiller(array_filter([$frais->id, $partenaire?->id]));
                // D3 : le marqueur est lu puis remis à 0 sous verrou, donc signalé une seule fois.
                if ($frais->dissolutionASignaler) {
                    $this->joueurs->effacerDissolutionASignaler($frais->id);
                    $evenements[] = Evenement::DuoDissous;
                }
            }
            $this->pdo->commit();
        } catch (Throwable $erreur) {
            $this->pdo->rollBack();
            throw $erreur;
        }
        return $evenements;
    }
}
