<?php
// Journée de jeu — C1 Accueil — parcours 2 (journée de jeu) — lot 1 — B. Bornes de la journée en Europe/Paris (doc technique § 3.1).

class JourneeService
{
    // Le dépôt est construit par l'appelant, contrôleur ou test (ADR-0001).
    public function __construct(private ParticipationDepot $participations) {}

    // Identifiant de journée AAAA-MM-JJ, clé de participation et de tirage.
    public function journee(DateTimeImmutable $maintenant): string
    {
        return self::enHeureDeParis($maintenant)->format('Y-m-d');
    }

    // Bascule de 12 h du jour : fin des réponses et des choix (cadrage § 4.2).
    public function bascule(DateTimeImmutable $maintenant): DateTimeImmutable
    {
        return self::enHeureDeParis($maintenant)->modify('today 12:00');
    }

    // Expiration des défis du jour (cadrage § 4.1). « tomorrow » suit le calendrier : 23 h ou 25 h après l'ouverture un jour de changement d'heure.
    public function minuitSuivant(DateTimeImmutable $maintenant): DateTimeImmutable
    {
        return self::enHeureDeParis($maintenant)->modify('tomorrow');
    }

    public function avantBascule(DateTimeImmutable $maintenant): bool
    {
        return $maintenant < $this->bascule($maintenant);
    }

    // Réponse à « On joue aujourd'hui ? ». Une seconde réponse (double clic, second appareil) est ignorée sans erreur : la clé unique la refuse, la première reste définitive.
    public function repondre(Joueur $joueur, Reponse $reponse, DateTimeImmutable $maintenant): void
    {
        // Un joueur relu sans duo (dissolution entre-temps) n'a pas de journée à laquelle répondre.
        if ($joueur->duoId === null) {
            return;
        }
        $this->participations->ajouter($joueur->duoId, $joueur->id, self::enHeureDeParis($maintenant), $reponse, $maintenant);
    }

    // État de C1 pour ce joueur, déduit des participations du jour ; rien n'est écrit (la journée blanche non plus).
    public function etatAccueil(Joueur $joueur, DateTimeImmutable $maintenant): EtatAccueil
    {
        $sienne = null;
        $celleDuPartenaire = null;
        foreach ($this->participations->trouverParDuoEtJour($joueur->duoId, self::enHeureDeParis($maintenant)) as $participation) {
            if ($participation->joueurId === $joueur->id) {
                $sienne = $participation->reponse;
            } else {
                $celleDuPartenaire = $participation->reponse;
            }
        }
        // Un seul non suffit : la question n'est plus posée à l'autre (spec #57, récit 4).
        if ($sienne === Reponse::Non || $celleDuPartenaire === Reponse::Non) {
            return EtatAccueil::AucunJeu;
        }
        if ($sienne === null) {
            return EtatAccueil::Question;
        }
        return $celleDuPartenaire === null ? EtatAccueil::AttentePartenaire : EtatAccueil::LesDeuxOntDitOui;
    }

    // La journée se lit à Paris quel que soit le fuseau de l'instant reçu : le service ne dépend pas du fuseau par défaut posé par l'amorce.
    private static function enHeureDeParis(DateTimeImmutable $maintenant): DateTimeImmutable
    {
        return $maintenant->setTimezone(new DateTimeZone('Europe/Paris'));
    }
}
