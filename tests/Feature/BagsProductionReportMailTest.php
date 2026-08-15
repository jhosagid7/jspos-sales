<?php

namespace Tests\Feature;

use App\Livewire\Production\ProductionList;
use App\Mail\BagsProductionConsolidatedMail;
use App\Mail\ProductionReportMail;
use App\Models\Production;
use App\Models\Configuration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class BagsProductionReportMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.installed' => true]);

        // Mock LicenseService
        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('checkLicense')->andReturn([
                'status' => 'active',
                'days_remaining' => 30,
                'modules' => [],
                'max_devices' => 10,
            ]);
            $mock->shouldReceive('getClientId')->andReturn('test-client-id');
        });
    }

    /**
     * Test ProductionReportMail can be queued and JSON encoded with binary PDF content.
     */
    public function test_production_report_mail_json_serialization_with_binary_pdf()
    {
        // Generate random binary data containing non-UTF8 characters
        $binaryPdfContent = "%PDF-1.4\n%\xe2\xe3\xcf\xd3\n" . random_bytes(500) . "\xff\xfe\x80\x90";
        $subject = "Reporte de Producción - Test";
        $body = "Este es un reporte con tildes y caracteres especiales: Canción, Producción, Ñandú.";
        $fileName = "produccion_123.pdf";

        $mailable = new ProductionReportMail($subject, $body, $binaryPdfContent, $fileName);

        // Verify mailable can be serialized to JSON without UTF-8 malformed character errors
        $serializedMailable = serialize($mailable);
        $jsonEncoded = json_encode(['mailable' => $serializedMailable]);

        $this->assertNotFalse($jsonEncoded, 'JSON encoding payload failed: ' . json_last_error_msg());
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());

        // Verify attachments retain original binary data when evaluated
        $attachments = $mailable->attachments();
        $this->assertCount(1, $attachments);

        $attachmentData = $attachments[0]->attachWith(
            fn () => null,
            fn ($dataClosure) => $dataClosure()
        );
        $this->assertEquals($binaryPdfContent, $attachmentData);
    }

    /**
     * Test BagsProductionConsolidatedMail can be queued and JSON encoded with binary PDFs.
     */
    public function test_bags_production_consolidated_mail_json_serialization_with_binary_pdf()
    {
        $binaryPdf1 = "%PDF-1.4\n1\n" . random_bytes(300) . "\xff\xfe";
        $binaryPdf2 = "%PDF-1.4\n2\n" . random_bytes(300) . "\x80\x81";
        
        $pdfs = [
            ['content' => $binaryPdf1, 'name' => 'pdf_1.pdf'],
            ['content' => $binaryPdf2, 'name' => 'pdf_2.pdf'],
        ];

        $mailable = new BagsProductionConsolidatedMail("Consolidado Test", "Cuerpo del correo", $pdfs);

        $serializedMailable = serialize($mailable);
        $jsonEncoded = json_encode(['mailable' => $serializedMailable]);

        $this->assertNotFalse($jsonEncoded, 'JSON encoding payload failed: ' . json_last_error_msg());
        $this->assertEquals(JSON_ERROR_NONE, json_last_error());

        $attachments = $mailable->attachments();
        $this->assertCount(2, $attachments);

        $data1 = $attachments[0]->attachWith(fn () => null, fn ($dataClosure) => $dataClosure());
        $data2 = $attachments[1]->attachWith(fn () => null, fn ($dataClosure) => $dataClosure());

        $this->assertEquals($binaryPdf1, $data1);
        $this->assertEquals($binaryPdf2, $data2);
    }

    /**
     * Test resending email from ProductionList Livewire component successfully queues the mail without error.
     */
    public function test_production_list_resend_email_queues_mailable_successfully()
    {
        Mail::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        // Configure recipient email
        $config = Configuration::first();
        if (!$config) {
            $config = new Configuration();
            $config->business_name = 'Fábrica de Bolsas Test';
        }
        $config->production_email_recipients = 'cliente@empresa.com';
        $config->save();

        $production = Production::create([
            'user_id' => $user->id,
            'production_date' => now()->toDateString(),
            'shift' => 'Mañana',
            'status' => 'processed',
            'note' => 'Bolsa de fábrica de prueba',
        ]);

        Livewire::test(ProductionList::class)
            ->call('sendEmail', $production->id)
            ->assertDispatched('noty', msg: 'Reporte enviado correctamente');

        Mail::assertQueued(ProductionReportMail::class, function ($mail) {
            $serialized = serialize($mail);
            $json = json_encode(['payload' => $serialized]);
            return $json !== false && $mail->hasTo('cliente@empresa.com');
        });
    }
}
