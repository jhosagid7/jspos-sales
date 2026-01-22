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

    public function mount()
    {
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

    public function editPrinter($id)
    {
        $device = DeviceAuthorization::find($id);
        if ($device) {
            $this->selected_device_id = $id;
            $this->selected_device_id = $id;
            $this->printer_name = $device->printer_name;
            $this->printer_width = $device->printer_width ?? '80mm';
            $this->is_network = (bool) $device->is_network;
            $this->printer_user = $device->printer_user;
            $this->printer_password = $device->printer_password;

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
                     $this->printer_host = $clean; // Fallback
                }
            } elseif ($this->is_network && !empty($this->printer_name)) {
                // Try to handle case where user entered just Name but it IS network
                 $this->printer_share = $this->printer_name;
            }

            $this->dispatch('show-modal', 'modalPrinter');
        }
    }

    public function updatePrinter()
    {
        $device = DeviceAuthorization::find($this->selected_device_id);
        if ($device) {
            
            // If network, construct the printer name from Host + Share
            if ($this->is_network) {
                 // Format: \\HOST\SHARE
                 $host = trim($this->printer_host, '\\'); // remove any leading/trailing slashes user might have typed
                 $share = trim($this->printer_share, '\\');
                 
                 if (empty($host)) {
                     // If host is empty, we can't make a valid UNC. 
                     // Fallback to just share? Or error? 
                     // For now let's assume user knows what they are doing or handled validation
                 }
                 
                 $this->printer_name = "\\\\" . $host . "\\" . $share;
            }

            $device->printer_name = $this->printer_name;
            $device->printer_width = $this->printer_width;
            $device->is_network = $this->is_network;
            $device->printer_user = $this->printer_user;
            $device->printer_password = $this->printer_password;
            $device->save();
            $this->dispatch('noty', msg: 'Configuración de impresora actualizada');
            $this->dispatch('close-modal', 'modalPrinter');
        }
    }
}
