<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CreditAuthorization;

class CreditAuthorizationsList extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    protected $paginationTheme = 'bootstrap';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = CreditAuthorization::with(['customer', 'requestedBy', 'approvedBy', 'sale'])
            ->orderBy('created_at', 'desc');

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->whereHas('customer', function($q2) {
                    $q2->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('identification_number', 'like', '%' . $this->search . '%');
                })->orWhere('pin_code', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->status)) {
            $query->where('status', $this->status);
        }

        $authorizations = $query->paginate(15);

        return view('livewire.credit-authorizations-list', [
            'authorizations' => $authorizations
        ])->extends('layouts.theme.app')->section('content');
    }
}
