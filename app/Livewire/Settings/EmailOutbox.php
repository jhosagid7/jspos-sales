<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\EmailMessage;
use App\Jobs\SendEmailNotification;

class EmailOutbox extends Component
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
        $message = EmailMessage::find($id);
        
        if ($message) {
            $currentEmail = null;
            if ($message->customer_id) {
                $customer = \App\Models\Customer::find($message->customer_id);
                if ($customer) {
                    $currentEmail = $customer->email;
                    if (empty($currentEmail) && $customer->seller) {
                        $currentEmail = $customer->seller->email;
                    }
                }
            }
            
            $emailToUse = !empty($currentEmail) ? $currentEmail : $message->email_address;

            $message->update([
                'status' => 'pending',
                'error_message' => null,
                'email_address' => $emailToUse
            ]);
            
            SendEmailNotification::dispatch($message->id);
            $this->dispatch('noty', msg: 'Correo reencolado. Se usará el email actual del cliente.');
        } else {
             $this->dispatch('msg-error', msg: 'Mensaje no encontrado.');
        }
    }

    public function render()
    {
        $messages = EmailMessage::with(['customer'])
            ->when($this->search, function($q) {
                $q->whereHas('customer', function($q2) {
                    $q2->where('name', 'like', "%{$this->search}%")
                       ->orWhere('email', 'like', "%{$this->search}%");
                })->orWhere('email_address', 'like', "%{$this->search}%");
            })
            ->when($this->statusFilter, function($q) {
                $q->where('status', $this->statusFilter);
            })
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.settings.email-outbox', [
            'messages' => $messages
        ])->extends('layouts.theme.app')
          ->section('content');
    }
}
