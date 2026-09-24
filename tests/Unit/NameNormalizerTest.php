<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\NameNormalizer;

class NameNormalizerTest extends TestCase
{
    /** @test */
    public function it_normalizes_person_names_to_uppercase()
    {
        $this->assertEquals('MARIA GUILLEN', NameNormalizer::personOrEntityName('maria guillen'));
        $this->assertEquals('MARIA GUILLEN', NameNormalizer::personOrEntityName('MARIA GUILLEN'));
        $this->assertEquals('MARIA GUILLEN', NameNormalizer::personOrEntityName('  maria    guillen  '));
        $this->assertEquals('MARÍA ÁNGEL PEÑA', NameNormalizer::personOrEntityName('maría ángel peña'));
        $this->assertEquals('JUAN DE LA ROSA', NameNormalizer::personOrEntityName('JUAN DE LA ROSA'));
        $this->assertEquals('PEDRO DEL VALLE', NameNormalizer::personOrEntityName('pedro del valle'));
    }

    /** @test */
    public function it_handles_acronyms_in_entity_names()
    {
        $this->assertEquals('INVERSIONES LOS LLANOS C.A.', NameNormalizer::personOrEntityName('inversiones los llanos c.a.'));
        $this->assertEquals('DISTRIBUIDORA EL SOL S.A.', NameNormalizer::personOrEntityName('distribuidora el sol s.a.'));
        $this->assertEquals('COMERCIALIZADORA PRO S.R.L.', NameNormalizer::personOrEntityName('comercializadora pro s.r.l.'));
    }

    /** @test */
    public function it_normalizes_uppercase_correctly()
    {
        $this->assertEquals('HARINA PAN 1KG', NameNormalizer::uppercase('harina pan 1kg'));
        $this->assertEquals('DEPÓSITO CENTRAL', NameNormalizer::uppercase('depósito central'));
        $this->assertEquals('BODEGA PRINCIPAL', NameNormalizer::uppercase('  bodega   principal  '));
        $this->assertEquals('BOLSA 30X40 CAÑÓN', NameNormalizer::uppercase('bolsa 30x40 cañón'));
    }

    /** @test */
    public function it_handles_null_and_empty_strings()
    {
        $this->assertNull(NameNormalizer::personOrEntityName(null));
        $this->assertEquals('', NameNormalizer::personOrEntityName(''));
        $this->assertEquals('', NameNormalizer::personOrEntityName('   '));

        $this->assertNull(NameNormalizer::uppercase(null));
        $this->assertEquals('', NameNormalizer::uppercase(''));
        $this->assertEquals('', NameNormalizer::uppercase('   '));
    }
}
