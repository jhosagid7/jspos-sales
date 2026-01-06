<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Customer;
use Livewire\WithPagination;


class Customers extends Component
{
    use WithPagination;

    public Customer $customer;
    public $sellers = [];
    public $search;
    public $editing;

    protected $rules = [
        'customer.name' => 'required|max:45|unique:customers,name',
        'customer.taxpayer_id' => 'required|max:45',
        'customer.address' => 'required|max:255',
        'customer.city' => 'required|max:100',
        'customer.phone' => 'nullable|max:15',
        'customer.email' => 'nullable|email|max:65',
        'customer.type' => 'required|in:Mayoristas,Consumidor Final,Descuento1,Descuento2,Otro',
        'customer.seller_id' => 'nullable',
    ];

    protected $messages = [
        'customer.name.required' => 'El nombre del cliente es requerido',
        'customer.name.max' => 'El nombre del cliente no puede tener más de 45 caracteres.',
        'customer.name.unique' => 'El nombre del cliente ya existe',
        'customer.address.max' => 'La dirección solo puede tener máximo 255 caracteres',
        'customer.address.required' => 'La dirección del cliente es requerido',
        'customer.city.max' => 'La ciudad solo puede tener máximo 100 caracteres',
        'customer.city.required' => 'La ciudad del cliente es requerida',
        'customer.phone.max' => 'Ingresa el teléfono en máximo 15 caracteres',
        'customer.type.in' => 'Elige una opción válida para el tipo de cliente',
        'customer.taxpayer_id.max' => 'La cc/nit solo puede tener máximo 45 caracteres',
        'customer.taxpayer_id.required' => 'La cc/nit del cliente es requerido',
    ];


    public function mount()
    {
        $this->customer = new Customer();
        $this->customer->type = 0;
        $this->customer->seller_id = 0;
        $this->editing = false;

        session(['map' => 'Clientes', 'child' => ' Componente ']);
    }



    public function render()
    {
        $this->sellers = \App\Models\User::role('Vendedor')->get();
        
        return view('livewire.customers.customers', [
            'customers' => $this->loadCustomers()
        ]);
    }

    public function loadCustomers()
    {
        if (strlen($this->search) > 0) {
            return Customer::where('name', 'like', "%{$this->search}%")
                ->orWhere('taxpayer_id', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%")
                ->orderBy('name', 'asc')
                ->paginate(10);
        } else {
            return Customer::orderBy('name', 'asc')->paginate(10);
        }
    }

    // ... (searching and loadCustomers methods remain unchanged)

    public function Add()
    {
        $this->resetValidation();
        $this->resetExcept('customer');
        $this->customer = new Customer();
        $this->customer->seller_id = 0;
        $this->dispatch('init-new');
    }

    public function Edit(Customer $customer)
    {
        $this->resetValidation();
        $this->customer = $customer;
        $this->editing = true;
    }

    public function cancelEdit()
    {
        $this->resetValidation();
        $this->customer = new Customer();
        $this->editing = false;
        $this->search = null;
        $this->dispatch('init-new');
    }



    public function Store()
    {
        $this->rules['customer.name'] = $this->customer->id > 0 ? "required|max:45|unique:customers,name,{$this->customer->id}" : 'required|max:45|unique:customers,name';

        $this->validate($this->rules, $this->messages);

        // Assign default seller if not selected
        if (!$this->customer->seller_id || $this->customer->seller_id == 0) {
            $defaultSeller = \App\Models\User::where('name', 'OFICINA')->first();
            if ($defaultSeller) {
                $this->customer->seller_id = $defaultSeller->id;
            }
        }

        // save model
        $this->customer->save();



        $this->dispatch('noty', msg: 'CLIENTE SE GUARDO CORRECTAMENTE');
        $this->resetExcept('customer');
        $this->customer = new Customer();
        $this->customer->type = 0;
        $this->customer->seller_id = 0;
    }


    public function Destroy(Customer $customer)
    {

        if ($customer->sales->count() > 0) {
            $this->dispatch('noty', msg: 'EL CLIENTE TIENE VENTAS RELACIONADAS, NO ES POSIBLE ELIMINARLO');
            return;
        }

        // delete record from db
        $customer->delete();

        $this->resetPage();


        $this->dispatch('noty', msg: 'CLIENTE ELIMINADO CON ÉXITO');
    }
}
