<?php
// Tests de cohérence du schéma SQL avec les énumérations PHP — aucun écran — lot 1 — commun.

use PHPUnit\Framework\TestCase;

class SchemaTest extends TestCase
{
    // L'ENUM de tentative.type est écrit à la main dans sql/schema.sql : il doit lister exactement les cas de TypeTentative.
    public function testLEnumDeTentativeTypeCorrespondATypeTentative(): void
    {
        $schema = file_get_contents(__DIR__ . '/../sql/schema.sql');
        $this->assertSame(1, preg_match("/CREATE TABLE tentative \(.*?type ENUM\(([^)]*)\)/s", $schema, $m));

        $dansLeSchema = array_map(fn(string $v) => trim($v, " '"), explode(',', $m[1]));
        $dansLeCode = array_map(fn(TypeTentative $t) => $t->value, TypeTentative::cases());

        sort($dansLeSchema);
        sort($dansLeCode);
        $this->assertSame($dansLeCode, $dansLeSchema);
    }
}
