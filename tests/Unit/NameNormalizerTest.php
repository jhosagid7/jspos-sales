<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Helpers\NameNormalizer;

class NameNormalizerTest extends TestCase
{
    /** @test */
    public function it_normalizes_person_names_correctly()
    {
        $this->assertEquals('Maria Guillen', NameNormalizer::personOrEntityName('maria guillen'));
        $this->assertEquals('Maria Guillen', NameNormalizer::personOrEntityName('MARIA GUILLEN'));
        $this->assertEquals('Maria Guillen', NameNormalizer::personOrEntityName('  maria    guillen  '));
        $this->assertEquals('María Ángel Peña', NameNormalizer::personOrEntityName('maría ángel peña'));
        $this->assertEquals('Juan de la Rosa', NameNormalizer::personOrEntityName('JUAN DE LA ROSA'));
        $this->assertEquals('Pedro del Valle', NameNormalizer::personOrEntityName('pedro del valle'));
    }

    /** @test */
    public function it_handles_acronyms_in_entity_names()
    {
        $this->assertEquals('Inversiones Los Llanos C.A.', NameNormalizer::personOrEntityName('inversiones los llanos c.a.'));
        $this->assertEquals('Distribuidora El Sol S.A.', NameNormalizer::personOrEntityName('distribuidora el sol s.a.'));
        $this->assertEquals('Comercializadora Pro S.R.L.', NameNormalizer::personOrEntityName('comercializadora pro s.r.l.'));
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
