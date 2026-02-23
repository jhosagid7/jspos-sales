<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\WhatsappMessage;
use App\Jobs\SendWhatsappMessage;

class WhatsappOutbox extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    protected $paginationTheme = 'bootstrap';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function retryMessage($id)
    {
        $message = WhatsappMessage::find($id);
        
        if ($message) {
            // Update the phone number to the customer's CURRENT phone number if available
            $currentPhone = null;
            if ($message->customer_id) {
                $customer = \App\Models\Customer::find($message->customer_id);
                if ($customer) {
                    $currentPhone = preg_replace('/[^0-9]/', '', $customer->phone);
                    // Fallback to seller if no customer phone
                    if (empty($currentPhone) && $customer->seller) {
                        $currentPhone = preg_replace('/[^0-9]/', '', $customer->seller->phone);
                    }
                }
            }
            
            // Si encontramos un numero nuevo, lo actualizamos, si no, usamos el que ya tenia
            $phoneToUse = !empty($currentPhone) ? $currentPhone : $message->phone_number;

            $message->update([
                'status' => 'pending',
                'error_message' => null,
                'phone_number' => $phoneToUse
            ]);
            
            SendWhatsappMessage::dispatch($message->id);
            $this->dispatch('noty', msg: 'Mensaje reencolado. Se usará el número actual del cliente.');
        } else {
             $this->dispatch('msg-error', msg: 'Mensaje no encontrado.');
        }
    }

    public function render()
    {
        $messages = WhatsappMessage::with(['customer'])
            ->when($this->search, function($q) {
                $q->whereHas('customer', function($q2) {
                    $q2->where('name', 'like', "%{$this->search}%")
                       ->orWhere('phone', 'like', "%{$this->search}%");
                })->orWhere('phone_number', 'like', "%{$this->search}%");
            })
            ->when($this->statusFilter, function($q) {
                $q->where('status', $this->statusFilter);
            })
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.settings.whatsapp-outbox', [
            'messages' => $messages
        ])->extends('layouts.theme.app')
          ->section('content');
    }
}

