<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Livewire\Settings\LicenseGenerator;
use Livewire\Livewire;

class LicenseGeneratorTest extends TestCase
{
    public function test_license_generator_renders_without_errors()
    {
        Livewire::test(LicenseGenerator::class)
            ->assertSee('Generador Maestro')
            ->assertSee('Configuración de Módulos Opcionales');
    }
}
