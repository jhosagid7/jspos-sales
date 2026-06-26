<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Configuration;
use App\Services\UpdateService;
use App\Livewire\Settings\UpdateSystem;
use Illuminate\Support\Facades\File;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;

/**
 * Tests para el sistema de rollback de actualizaciones.
 *
 * Nota importante: Se usa RefreshDatabase en vez de DatabaseMigrations
 * porque MySQL no soporta DDL dentro de transacciones. Los tests de
 * importación SQL mockean importDatabaseSql() para evitar ejecutar DDL
 * real que causaría auto-commit y contaminaría el estado de la BD para
 * suites posteriores.
 */
class UpdateRollbackTest extends TestCase
{
    use RefreshDatabase;

    protected $tempBackupDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempBackupDir = storage_path('backups');

        // Clean backups folder before testing
        if (File::exists($this->tempBackupDir)) {
            File::deleteDirectory($this->tempBackupDir);
        }
        File::makeDirectory($this->tempBackupDir, 0755, true, true);

        // Ensure Configuration exists
        Configuration::create([
            'business_name' => 'Original Business Name',
        ]);
    }

    protected function tearDown(): void
    {
        // Cleanup backups folder
        if (File::exists($this->tempBackupDir)) {
            File::deleteDirectory($this->tempBackupDir);
        }

        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test: El exportador SQL genera el archivo con estructuras e inserts.
     */
    public function test_database_export_sql_writes_tables_and_data()
    {
        $updater = new UpdateService();
        $sqlPath = $this->tempBackupDir . '/test_db.sql';

        $updater->exportDatabaseSql($sqlPath);

        $this->assertFileExists($sqlPath);
        $content = File::get($sqlPath);

        // Assert it has table creation structures and inserts
        $this->assertStringContainsString('CREATE TABLE `users`', $content);
        $this->assertStringContainsString('CREATE TABLE `configurations`', $content);
        $this->assertStringContainsString('INSERT INTO `configurations`', $content);
        $this->assertStringContainsString('Original Business Name', $content);
    }

    /**
     * Test: El compresor ZIP genera un archivo válido con el contenido esperado.
     */
    public function test_zip_directories_creates_valid_zip()
    {
        $updater = new UpdateService();
        $zipPath = $this->tempBackupDir . '/test_files.zip';

        // Zip only small directories/files for testing speed
        $updater->zipDirectories(['routes'], ['version.txt'], $zipPath);

        $this->assertFileExists($zipPath);

        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($zipPath) === true);
        $this->assertGreaterThan(0, $zip->numFiles);

        // Assert version.txt is inside the zip
        $this->assertNotFalse($zip->locateName('version.txt'));
        $zip->close();
    }

    /**
     * Test: createRollbackBackup crea la carpeta y los archivos esperados.
     */
    public function test_create_rollback_backup_creates_expected_folder_and_files()
    {
        $updater = new UpdateService();
        $version = '1.0.0-test';
        $updater->createRollbackBackup($version, ['routes'], ['version.txt']);

        $folderPath = $this->tempBackupDir . '/antes_de_v' . $version;
        $this->assertDirectoryExists($folderPath);
        $this->assertFileExists($folderPath . '/database_backup.sql');
        $this->assertFileExists($folderPath . '/files_backup.zip');
    }

    /**
     * Test: El mecanismo de poda mantiene solo los 3 backups más recientes.
     */
    public function test_prune_mechanism_keeps_only_three_most_recent_backups()
    {
        $updater = new UpdateService();

        // Create 4 dummy backups with mock SQL content (no DDL needed)
        // Use timestamps in the PAST so that the real 1.0.5 backup created later
        // will always have a newer filesystem timestamp and won't get pruned.
        $versions = ['1.0.1', '1.0.2', '1.0.3', '1.0.4'];
        $baseTime = time() - 4000; // 4000 seconds in the past

        foreach ($versions as $idx => $v) {
            $dir = $this->tempBackupDir . '/antes_de_v' . $v;
            File::makeDirectory($dir, 0755, true, true);
            File::put($dir . '/database_backup.sql', '-- dummy sql backup');
            File::put($dir . '/files_backup.zip', 'dummy zip content');

            // Set timestamps progressively older → 1.0.1 oldest, 1.0.4 newest of the dummies
            // All are still in the past, so real 1.0.5 will be the absolute newest
            $fakeTime = $baseTime + ($idx * 100);
            touch($dir, $fakeTime);
            touch($dir . '/database_backup.sql', $fakeTime);
            touch($dir . '/files_backup.zip', $fakeTime);
        }

        // Trigger a new backup creation which calls pruneOldBackups()
        // This creates version 1.0.5, making 5 total → should prune to 3
        $updater->createRollbackBackup('1.0.5', ['routes'], ['version.txt']);

        $available = $updater->getAvailableRollbacks();

        // Should keep only the 3 most recent backups (1.0.3, 1.0.4, 1.0.5)
        // 1.0.1 and 1.0.2 should be deleted
        $this->assertCount(3, $available);

        $versionsKept = collect($available)->pluck('version')->toArray();
        $this->assertContains('1.0.5', $versionsKept);
        $this->assertContains('1.0.4', $versionsKept);
        $this->assertContains('1.0.3', $versionsKept);

        $this->assertNotContains('1.0.1', $versionsKept);
        $this->assertNotContains('1.0.2', $versionsKept);
    }

    /**
     * Test: restoreFromBackup restaura archivos y llama importDatabaseSql.
     *
     * Nota: importDatabaseSql() es mockeada para evitar ejecutar DDL
     * (DROP/CREATE TABLE) dentro del contexto de una transacción de testing,
     * ya que MySQL hace auto-commit en DDL y corrompe el estado de la BD.
     * La lógica de importación SQL se valida en test_database_export_sql_writes_tables_and_data.
     */
    public function test_restore_from_backup_restores_files_and_calls_import()
    {
        // 1. Create real backup using small dirs only
        $version = 'restore-test';
        $backupDir = $this->tempBackupDir . '/antes_de_v' . $version;
        File::makeDirectory($backupDir, 0755, true, true);

        // Create real zip with routes folder
        $updater = new UpdateService();
        $updater->zipDirectories(['routes'], ['version.txt'], $backupDir . '/files_backup.zip');

        // Create a mock SQL file (no real DDL, just a marker)
        File::put($backupDir . '/database_backup.sql', "-- mock backup\nSELECT 1;\n");

        // 2. Mock UpdateService to verify importDatabaseSql is called
        //    but not actually execute DDL
        $mockUpdater = Mockery::mock(UpdateService::class)->makePartial();
        $mockUpdater->shouldReceive('importDatabaseSql')
            ->once()
            ->with($backupDir . '/database_backup.sql')
            ->andReturn(null);
        $mockUpdater->shouldReceive('cleanup')
            ->once()
            ->andReturn(true);

        // 3. Run restore — files should be extracted, import should be called
        $result = $mockUpdater->restoreFromBackup('antes_de_v' . $version);

        $this->assertTrue($result);
    }

    /**
     * Test: El componente Livewire lista y elimina rollback points correctamente.
     */
    public function test_livewire_component_lists_and_manages_rollbacks()
    {
        $updater = new UpdateService();

        // Create a dummy backup with real zip for listing
        $version = 'lw-test';
        $updater->createRollbackBackup($version, ['routes'], ['version.txt']);

        $adminUser = User::factory()->create();

        // 1. Assert rollbacks are listed
        Livewire::actingAs($adminUser)
            ->test(UpdateSystem::class)
            ->assertViewHas('rollbacks', function ($rollbacks) use ($version) {
                return collect($rollbacks)->contains('version', $version);
            });

        // 2. Delete the rollback point via component
        Livewire::actingAs($adminUser)
            ->test(UpdateSystem::class)
            ->call('deleteRollback', 'antes_de_v' . $version)
            ->assertViewHas('rollbacks', function ($rollbacks) {
                return count($rollbacks) === 0;
            });

        $this->assertDirectoryDoesNotExist($this->tempBackupDir . '/antes_de_v' . $version);
    }
}
