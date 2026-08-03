<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\File;

class RemoteWipeTest extends TestCase
{
    protected function tearDown(): void
    {
        // Cleanup storage/wiped file if created during test
        if (File::exists(storage_path('wiped'))) {
            File::delete(storage_path('wiped'));
        }
        parent::tearDown();
    }

    public function test_remote_wipe_fails_without_token()
    {
        $response = $this->postJson('/api/license/remote-wipe');
        $response->assertStatus(401);
    }

    public function test_remote_wipe_fails_on_client_id_mismatch()
    {
        $response = $this->postJson('/api/license/remote-wipe', [
            'client_system_id' => 'different-uuid-9999'
        ], [
            'Authorization' => 'super_secret_token_123'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Orden ignorada: El ID de cliente no corresponde a esta instalación.'
            ]);
    }

    public function test_remote_wipe_executes_successfully_with_valid_token()
    {
        $response = $this->post('/api/license/remote-wipe', [], [
            'Authorization' => 'super_secret_token_123'
        ]);

        $response->assertStatus(200);
        $this->assertFileExists(storage_path('wiped'));
    }

    public function test_middleware_blocks_access_when_wiped()
    {
        // Create lock
        file_put_contents(storage_path('wiped'), 'TEST WIPED');

        $response = $this->get('/');
        $response->assertStatus(403);
        $response->assertSee('SISTEMA INHABILITADO');
    }
}
