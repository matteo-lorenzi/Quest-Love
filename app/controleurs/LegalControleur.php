<?php
// Contrôleur des pages légales — A4 — parcours 1 (appairage) — lot 1 — A.

class LegalControleur
{
    // Liste fermée des pages : paramètre « page » de l'adresse => titre.
    public const PAGES = [
        'mentions' => 'Mentions légales',
        'confidentialite' => 'Politique de confidentialité',
        'conditions' => "Conditions d'utilisation",
    ];

    // Le routeur passe la connexion et l'heure ; A4 n'a besoin ni de l'un ni de l'autre (ADR-0001).
    public function __construct(private PDO $pdo, private DateTimeImmutable $maintenant) {}

    // GET seulement : une vue unique, la page choisie par &page= (doc technique § 5.1).
    public function afficher(): void
    {
        $page = self::page($_GET['page'] ?? null);
        Vue::rendre('a4-legal', self::PAGES[$page], ['page' => $page, 'pages' => self::PAGES]);
    }

    // Page validée contre la liste fermée ; toute autre valeur (absente, inconnue, tableau) : les mentions légales.
    public static function page(mixed $page): string
    {
        return is_string($page) && isset(self::PAGES[$page]) ? $page : 'mentions';
    }
}
