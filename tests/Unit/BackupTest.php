<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Livewire\Settings\Backups;
use Livewire\Livewire;

class BackupTest extends TestCase
{
    public function test_backup_create_only_db_does_not_fail()
    {
        Livewire::test(Backups::class)
            ->call('create', 'only-db')
            ->assertDispatched('noty', msg: 'Copia de seguridad creada exitosamente');
    }
}
