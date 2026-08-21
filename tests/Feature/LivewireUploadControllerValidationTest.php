<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Controllers\LivewireUploadController;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;

class LivewireUploadControllerValidationTest extends TestCase
{
    public function test_validate_and_store_catches_invalid_php_upload_cleanly()
    {
        $controller = new LivewireUploadController();
        $invalidFile = new UploadedFile('', 'test.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE, true);

        $this->expectException(ValidationException::class);
        $controller->validateAndStore([$invalidFile], 'public');
    }

    public function test_validate_and_store_saves_valid_file_and_returns_path()
    {
        Storage::fake('public');
        $controller = new LivewireUploadController();
        $file = UploadedFile::fake()->image('producto_belvita.jpg', 600, 600);

        $paths = $controller->validateAndStore([$file], 'public');

        $this->assertNotEmpty($paths);
        $this->assertCount(1, $paths);
        $storedFilename = $paths->first();
        $this->assertStringContainsString('producto_belvita.jpg', base64_decode(head(explode('-', last(explode('-meta', str_replace('_', '/', $storedFilename)))))));

        $expectedRelativePath = FileUploadConfiguration::path($storedFilename);
        $this->assertTrue(Storage::disk('public')->exists($expectedRelativePath), 'Temporary file must be stored in public disk');
    }

    public function test_validate_and_store_handles_multiple_files_successfully()
    {
        Storage::fake('public');
        $controller = new LivewireUploadController();
        $file1 = UploadedFile::fake()->image('foto1.jpg');
        $file2 = UploadedFile::fake()->image('foto2.png');

        $paths = $controller->validateAndStore([$file1, $file2], 'public');

        $this->assertCount(2, $paths);
        foreach ($paths as $storedFilename) {
            $expectedRelativePath = FileUploadConfiguration::path($storedFilename);
            $this->assertTrue(Storage::disk('public')->exists($expectedRelativePath));
        }
    }
}

