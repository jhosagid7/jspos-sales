<?php

namespace Tests\Unit\Fiscal;

use App\Services\Fiscal\Drivers\FakeDriver;
use PHPUnit\Framework\TestCase;

class FakeDriverTest extends TestCase
{
    public function test_records_batches_in_order(): void
    {
        $d = new FakeDriver();
        $d->sendBatch(['iR*1', 'iS*A']);
        $d->send('I0X');

        $this->assertSame(['iR*1', 'iS*A', 'I0X'], $d->sentCommands());
        $this->assertTrue($d->hasSent('I0'));
        $this->assertFalse($d->hasSent('I0Z'));
    }

    public function test_forced_error_sends_nothing(): void
    {
        $d = new FakeDriver();
        $d->forceError('send', 'paper out');
        $r = $d->sendBatch(['101']);

        $this->assertFalse($r->success);
        $this->assertSame('paper out', $r->error);
        $this->assertSame([], $d->sentCommands());
    }

    public function test_status_is_a_valid_s1(): void
    {
        $d = new FakeDriver();
        $d->setLastInvoice(99);
        $s1 = (new \App\Services\Fiscal\FiscalStatusParser())->parseS1($d->readStatus()->raw);

        $this->assertSame('ZKF0000001', $s1['serial']);
        $this->assertSame('00000099', $s1['last_invoice']);
    }

    public function test_offline_toggle(): void
    {
        $d = new FakeDriver();
        $d->goOffline();
        $this->assertFalse($d->isConnected());
        $d->goOnline();
        $this->assertTrue($d->isConnected());
    }
}
