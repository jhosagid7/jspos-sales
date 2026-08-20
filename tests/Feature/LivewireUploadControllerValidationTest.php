<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Controllers\LivewireUploadController;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class LivewireUploadControllerValidationTest extends TestCase
{
    public function test_validate_and_store_catches_invalid_php_upload_cleanly()
    {
        $controller = new LivewireUploadController();
        $invalidFile = new UploadedFile('', 'test.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE, true);

        $this->expectException(ValidationException::class);
        $controller->validateAndStore([$invalidFile], 'public');
    }
}
