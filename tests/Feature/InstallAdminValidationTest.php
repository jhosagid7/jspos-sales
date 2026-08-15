<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InstallAdminValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_displays_validation_errors_when_password_confirmation_fails()
    {
        $response = $this->post(route('install.createAdmin'), [
            'name' => 'Juan Perez',
            'email' => 'juan@example.com',
            'password' => '12345678',
            'password_confirmation' => 'mismatch123',
        ]);

        $response->assertSessionHasErrors(['password']);
        $response->assertRedirect();
        
        $followUp = $this->get(route('install.step5'));
        $followUp->assertSee('Por favor corrija los siguientes errores:');
        $followUp->assertSee('La confirmación de la contraseña no coincide.');
    }

    /** @test */
    public function it_creates_admin_successfully_when_data_is_valid()
    {
        $response = $this->post(route('install.createAdmin'), [
            'name' => 'Juan Perez',
            'email' => 'juan@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'email' => 'juan@example.com',
            'name' => 'Juan Perez',
        ]);
    }
}
