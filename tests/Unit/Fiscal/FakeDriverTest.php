<?php

namespace Tests\Unit\Fiscal;

use App\Services\Fiscal\Drivers\FakeDriver;
use App\Services\Fiscal\ValueObjects\FiscalResponse;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the FakeDriver.
 *
 * Ensures the fake driver behaves correctly so that tests
 * relying on it are reliable and predictable.
 */
class FakeDriverTest extends TestCase
{
    private FakeDriver $driver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->driver = new FakeDriver();
    }

    /** @test */
    public function driver_name_is_fake(): void
    {
        $this->assertSame('fake', $this->driver->driverName());
    }

    /** @test */
    public function driver_starts_connected(): void
    {
        $this->assertTrue($this->driver->isConnected());
    }

    /** @test */
    public function go_offline_disconnects_driver(): void
    {
        $this->driver->goOffline();
        $this->assertFalse($this->driver->isConnected());
    }

    /** @test */
    public function go_online_reconnects_driver(): void
    {
        $this->driver->goOffline();
        $this->driver->goOnline();
        $this->assertTrue($this->driver->isConnected());
    }

    /** @test */
    public function send_records_command(): void
    {
        $this->driver->send('T01CLIENTE@J12345678');
        $this->assertCount(1, $this->driver->sentCommands());
        $this->assertSame('T01CLIENTE@J12345678', $this->driver->sentCommands()[0]);
    }

    /** @test */
    public function multiple_sends_are_all_recorded(): void
    {
        $this->driver->send('T01...');
        $this->driver->send('!Item1@1.000@100.00');
        $this->driver->send('T');
        $this->assertCount(3, $this->driver->sentCommands());
    }

    /** @test */
    public function has_sent_matches_by_prefix(): void
    {
        $this->driver->send('T01CLIENTE');
        $this->assertTrue($this->driver->hasSent('T01'));
        $this->assertFalse($this->driver->hasSent('T41'));
    }

    /** @test */
    public function send_returns_success_response_by_default(): void
    {
        $response = $this->driver->send('T');
        $this->assertTrue($response->success);
        $this->assertInstanceOf(FiscalResponse::class, $response);
    }

    /** @test */
    public function force_error_on_send_returns_failure(): void
    {
        $this->driver->forceError('send', 'Printer paper out');
        $response = $this->driver->send('!Item@1.000@100.00');
        $this->assertFalse($response->success);
        $this->assertSame('Printer paper out', $response->error);
    }

    /** @test */
    public function read_s1_returns_success_with_fiscal_fields(): void
    {
        $response = $this->driver->readS1();
        $this->assertTrue($response->success);
        $this->assertNotNull($response->lastInvoiceNumber());
        $this->assertNotNull($response->printerSerial());
        $this->assertNotNull($response->zCounter());
    }

    /** @test */
    public function force_error_on_s1_returns_failure(): void
    {
        $this->driver->forceError('s1', 'S1 read timeout');
        $response = $this->driver->readS1();
        $this->assertFalse($response->success);
        $this->assertSame('S1 read timeout', $response->error);
    }

    /** @test */
    public function read_s2_returns_success_by_default(): void
    {
        $response = $this->driver->readS2();
        $this->assertTrue($response->success);
    }

    /** @test */
    public function reset_clears_all_recorded_commands(): void
    {
        $this->driver->send('T');
        $this->driver->send('Z');
        $this->driver->reset();
        $this->assertCount(0, $this->driver->sentCommands());
    }

    /** @test */
    public function set_invoice_counter_affects_s1_response(): void
    {
        $this->driver->setInvoiceCounter(99999);
        $response = $this->driver->readS1();
        $this->assertStringContainsString('99999', $response->lastInvoiceNumber());
    }
}
