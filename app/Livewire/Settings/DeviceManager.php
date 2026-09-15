<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\DeviceAuthorization;
use App\Models\Configuration;
use Livewire\WithPagination;
use Illuminate\Support\Str;

class DeviceManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search = '', $access_mode = 'open';
    public $selected_device_id, $new_name;
    public $printer_name, $printer_width = '80mm';
    public $is_network = false, $printer_user = '', $printer_password = '';
    public $printer_host = '', $printer_share = '';
    public $is_editing_printer = false;
    public $current_token;
    public $discovered_printers = [];
    public $is_scanning = false;
    public $connection_test_result = null;

    public function mount()
    {
        $this->current_token = \Illuminate\Support\Facades\Cookie::get('device_token') ?? session('device_token');
        $config = \App\Models\Configuration::first();
        $this->access_mode = $config->device_access_mode ?? 'open';
    }

    public function render()
    {
        $devices = DeviceAuthorization::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('ip_address', 'like', '%' . $this->search . '%')
                    ->orWhere('user_agent', 'like', '%' . $this->search . '%');
            })
            ->orderBy('last_accessed_at', 'desc')
            ->paginate(10);

        return view('livewire.settings.device-manager', [
            'devices' => $devices
        ])->extends('layouts.theme.app')->section('content');
    }

    public function toggleAccessMode()
    {
        $newMode = $this->access_mode === 'open' ? 'restricted' : 'open';
        
        $config = \App\Models\Configuration::first();
        if ($config) {
            $config->device_access_mode = $newMode;
            $config->save();
            $this->access_mode = $newMode;
            $this->dispatch('noty', msg: 'Modo de acceso actualizado a: ' . ($newMode == 'open' ? 'ABIERTO' : 'RESTRINGIDO'));
        }
    }

    public function approve($id)
    {
        $device = DeviceAuthorization::find($id);
        if ($device) {
            $maxDevices = config('tenant.max_devices', 1);
            $approvedCount = DeviceAuthorization::where('status', 'approved')->count();

            if ($device->status !== 'approved' && $approvedCount >= $maxDevices) {
                $this->dispatch('msg-error', msg: "Límite de dispositivos alcanzado ({$maxDevices}). Actualice su plan de suscripción.");
                return;
            }

            $device->status = 'approved';
            $device->save();
            $this->dispatch('noty', msg: 'Dispositivo aprobado correctamente');
        }
    }

    public function block($id)
    {
        $device = DeviceAuthorization::find($id);
        if ($device) {
            $device->status = 'blocked';
            $device->save();
            $this->dispatch('noty', msg: 'Dispositivo bloqueado');
        }
    }

    public function delete($id)
    {
        $device = DeviceAuthorization::find($id);
        if ($device) {
            $device->delete();
            $this->dispatch('noty', msg: 'Dispositivo eliminado');
        }
    }

    public function updateName($id, $name)
    {
        $device = DeviceAuthorization::find($id);
        if ($device) {
            $device->name = $name;
            $device->save();
            $this->dispatch('noty', msg: 'Nombre actualizado');
        }
    }

    public function scanPrinters()
    {
        $this->is_scanning = true;
        $this->connection_test_result = null;
        try {
            $this->discovered_printers = \App\Services\PrinterDiscoveryService::discoverPrinters();
            $count = count($this->discovered_printers);
            if ($count > 0) {
                $this->dispatch('noty', msg: "Se encontraron {$count} impresoras disponibles.");
            } else {
                $this->dispatch('noty', msg: "No se detectaron impresoras activas en la red ni locales.");
            }
        } catch (\Throwable $e) {
            $this->dispatch('msg-error', msg: "Error al escanear impresoras: " . $e->getMessage());
        } finally {
            $this->is_scanning = false;
        }
    }

    public function selectDiscoveredPrinter($unc)
    {
        if (empty($unc)) {
            return;
        }

        $this->printer_name = $unc;
        $this->connection_test_result = null;

        if (Str::startsWith($unc, '\\\\')) {
            $this->is_network = true;
            $clean = str_replace('\\\\', '', $unc);
            $parts = explode('\\', $clean);
            if (count($parts) >= 2) {
                $this->printer_host = $parts[0];
                $this->printer_share = $parts[1];
            } else {
                $this->printer_host = $clean;
                $this->printer_share = '';
            }
        } else {
            $this->is_network = false;
            $this->printer_host = '';
            $this->printer_share = $unc;
        }
    }

    public function resolvePrinterTargetName(): string
    {
        if ($this->is_network) {
            $host = trim($this->printer_host ?? '', "\\ \t\n\r\0\x0B");
            $share = trim($this->printer_share ?? '', "\\ \t\n\r\0\x0B");
            if (!empty($host) && !empty($share)) {
                return "\\\\{$host}\\{$share}";
            }
        }
        return trim((string) $this->printer_name);
    }

    public function testPrinterConnection()
    {
        $target = $this->resolvePrinterTargetName();
        if (empty($target)) {
            $this->connection_test_result = [
                'success' => false,
                'message' => 'Por favor ingrese o seleccione una impresora antes de probar la conexión.',
                'latency_ms' => 0
            ];
            return;
        }

        $result = \App\Services\PrinterDiscoveryService::testConnection(
            $target,
            $this->printer_user ?? '',
            $this->printer_password ?? ''
        );
        $this->connection_test_result = $result;

        if ($result['success']) {
            $this->dispatch('noty', msg: $result['message']);
        } else {
            $this->dispatch('msg-error', msg: $result['message']);
        }
    }

    public function printTestTicket()
    {
        $target = $this->resolvePrinterTargetName();
        if (empty($target)) {
            $this->connection_test_result = [
                'success' => false,
                'message' => 'Por favor configure una impresora antes de imprimir un ticket de prueba.',
                'latency_ms' => 0
            ];
            return;
        }

        $result = \App\Services\PrinterDiscoveryService::printTestPage(
            $target,
            $this->printer_width ?? '80mm',
            $this->printer_user ?? '',
            $this->printer_password ?? ''
        );
        $this->connection_test_result = $result;

        if ($result['success']) {
            $this->dispatch('noty', msg: $result['message']);
        } else {
            $this->dispatch('msg-error', msg: $result['message']);
        }
    }

    public function purgeDuplicates()
    {
        $deletedCount = 0;
        
        // 1. Delete pending devices older than 14 days
        $deletedCount += DeviceAuthorization::where('status', 'pending')
            ->where(function ($q) {
                $q->where('created_at', '<', now()->subDays(14))
                  ->orWhere('last_accessed_at', '<', now()->subDays(14));
            })
            ->delete();

        // 2. Group approved devices by IP + User-Agent and delete older duplicates (keeping the most recently accessed)
        $approvedDevices = DeviceAuthorization::where('status', 'approved')
            ->orderBy('last_accessed_at', 'desc')
            ->get();

        $seen = [];
        $idsToDelete = [];

        foreach ($approvedDevices as $dev) {
            $key = $dev->ip_address . '|' . $dev->user_agent;

            // Always preserve the currently active device session, but mark its fingerprint as seen
            if ($this->current_token && $dev->uuid === $this->current_token) {
                $seen[$key] = true;
                continue;
            }

            if (isset($seen[$key])) {
                $idsToDelete[] = $dev->id;
            } else {
                $seen[$key] = true;
            }
        }

        if (!empty($idsToDelete)) {
            $deletedCount += DeviceAuthorization::whereIn('id', $idsToDelete)->delete();
        }

        $this->dispatch('noty', msg: "Se eliminaron {$deletedCount} dispositivos duplicados o inactivos.");
    }

    public function editPrinter($id)
    {
        $device = DeviceAuthorization::find($id);
        if ($device) {
            $this->selected_device_id = $id;
            $this->printer_name = $device->printer_name ?? '';
            $this->printer_width = $device->printer_width ?? '80mm';
            $this->is_network = (bool) $device->is_network;
            $this->printer_user = $device->printer_user ?? '';
            $this->printer_password = $device->printer_password ?? '';
            $this->connection_test_result = null;

            // Extract Host and Share from printer_name if it is network
            $this->printer_host = '';
            $this->printer_share = $this->printer_name;
            
            if ($this->is_network && Str::contains($this->printer_name, '\\')) {
                $clean = str_replace('\\\\', '', $this->printer_name);
                $parts = explode('\\', $clean);
                if (count($parts) >= 2) {
                    $this->printer_host = $parts[0];
                    $this->printer_share = $parts[1];
                } else {
                    $this->printer_host = $clean;
                }
            }

            // Auto-scan printers if empty
            if (empty($this->discovered_printers)) {
                $this->scanPrinters();
            }

            $this->dispatch('show-modal', 'modalPrinter');
        }
    }

    public function updatePrinter()
    {
        $device = DeviceAuthorization::find($this->selected_device_id);
        if ($device) {
            $target = $this->resolvePrinterTargetName();

            $device->printer_name = $target;
            $device->printer_width = $this->printer_width;
            $device->is_network = $this->is_network;
            $device->printer_user = $this->printer_user;
            $device->printer_password = $this->printer_password;
            $device->save();
            
            $this->printer_name = $device->printer_name;
            
            $this->dispatch('noty', msg: 'Configuración de impresora actualizada');
            $this->dispatch('close-modal', 'modalPrinter');
        }
    }
}
