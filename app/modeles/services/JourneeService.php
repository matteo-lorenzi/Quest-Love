<?php
// Journée de jeu — C1 Accueil — parcours 2 (journée de jeu) — lot 1 — B. Bornes de la journée en Europe/Paris (doc technique § 3.1).

class JourneeService
{
    // Identifiant de journée AAAA-MM-JJ, clé de participation et de tirage.
    public function journee(DateTimeImmutable $maintenant): string
    {
        return $maintenant->format('Y-m-d');
    }

    // Bascule de 12 h du jour : fin des réponses et des choix (cadrage § 4.2).
    public function bascule(DateTimeImmutable $maintenant): DateTimeImmutable
    {
        return $maintenant->modify('today 12:00');
    }

    // Expiration des défis du jour (cadrage § 4.1). « tomorrow » suit le calendrier : 23 h ou 25 h après l'ouverture un jour de changement d'heure.
    public function minuitSuivant(DateTimeImmutable $maintenant): DateTimeImmutable
    {
        return $maintenant->modify('tomorrow');
    }

    public function avantBascule(DateTimeImmutable $maintenant): bool
    {
        return $maintenant < $this->bascule($maintenant);
    }
}
