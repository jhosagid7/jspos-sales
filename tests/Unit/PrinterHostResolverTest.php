<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\CustomWindowsPrintConnector;

class PrinterHostResolverTest extends TestCase
{
    public function test_resolves_direct_ipv4_instantly()
    {
        $ip = '192.168.1.150';
        $resolved = CustomWindowsPrintConnector::resolveHostnameToIp($ip);
        $this->assertEquals($ip, $resolved);
    }

    public function test_resolves_localhost_to_loopback()
    {
        $resolved = CustomWindowsPrintConnector::resolveHostnameToIp('localhost');
        $this->assertEquals('127.0.0.1', $resolved);
    }

    public function test_resolves_tailscale_host_or_fallback()
    {
        $resolved = CustomWindowsPrintConnector::resolveHostnameToIp('serv-dev');
        $this->assertNotEmpty($resolved);
        $this->assertTrue($resolved === '100.64.0.4' || $resolved === 'serv-dev');
    }

    public function test_resolves_self_hostname_to_loopback()
    {
        $resolved = CustomWindowsPrintConnector::resolveHostnameToIp(gethostname());
        $this->assertEquals('127.0.0.1', $resolved);
    }

    public function test_system_tool_path_helpers()
    {
        $ps = CustomWindowsPrintConnector::getPowerShellPath();
        $this->assertNotEmpty($ps);
        $this->assertStringContainsString('powershell', strtolower($ps));

        $net = CustomWindowsPrintConnector::getSystemToolPath('net');
        $this->assertNotEmpty($net);

        $nbt = CustomWindowsPrintConnector::getSystemToolPath('nbtstat');
        $this->assertNotEmpty($nbt);

        $arp = CustomWindowsPrintConnector::getSystemToolPath('arp');
        $this->assertNotEmpty($arp);
    }

    public function test_connector_initialization_with_unc_and_smb()
    {
        $connector = new CustomWindowsPrintConnector('\\\\DESKTOP-7AMB0M0\\POS-80-Series');
        $this->assertInstanceOf(CustomWindowsPrintConnector::class, $connector);
        
        $ref = new \ReflectionProperty(CustomWindowsPrintConnector::class, 'buffer');
        $ref->setAccessible(true);
        $ref->setValue($connector, null);

        $connectorSmb = new CustomWindowsPrintConnector('smb://impresora:123@DESKTOP-7AMB0M0/POS-80-Series');
        $this->assertInstanceOf(CustomWindowsPrintConnector::class, $connectorSmb);
        $ref->setValue($connectorSmb, null);
    }
}
