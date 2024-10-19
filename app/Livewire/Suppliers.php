<?php

namespace App\Livewire;

use App\Models\Supplier;
use Livewire\Component;
use Livewire\WithPagination;



class Suppliers extends Component
{
    use WithPagination;



    public Supplier $supplier;
    public $supplier_id,   $editing, $search, $records, $pagination = 5;

    protected $paginationTheme = 'bootstrap';


    protected $rules =
    [
        'supplier.name' => "required|max:50|unique:suppliers,name",
        'supplier.address' => 'nullable|max:255',
        'supplier.phone' => 'nullable|max:15',
    ];

    protected $messages = [
        'supplier.name.required' => 'El nombre del proveedor es requerido',
        'supplier.name.max' => 'El nombre del proveedor no puede tener más de 255 caracteres.',
        'supplier.name.unique' => 'El nombre del proveedor ya existe',
        'supplier.address.max' => 'La dirección solo puede tener máximo 255 caracteres',
        'supplier.phone.max' => 'Ingresa el teléfono en máximo 15 caracteres',
    ];


    public function mount()
    {
        $this->supplier = new Supplier();
        $this->editing = false;

        session(['map' => 'Proveedores', 'child' => ' Componente ']);
    }

    protected $listeners = [
        'refresh' => '$refresh',
        'search' => 'searching',
        'Destroy'
    ];


    public function render()
    {
        return view('livewire.suppliers.suppliers', [
            'suppliers' => $this->loadSuppliers()
        ]);
    }

    public function searching($searchText)
    {
        $this->search = trim($searchText);
    }


    public function loadSuppliers()
    {
        if (!empty($this->search)) {

            $this->resetPage();

            $query = Supplier::where('name', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%")
                ->orWhere('address', 'like', "%{$this->search}%")
                ->orderBy('name', 'asc');
        } else {
            $query =  Supplier::orderBy('name', 'asc');
        }

        $this->records = $query->count();

        return $query->paginate($this->pagination);
    }


    public function Add()
    {
        $this->resetValidation();
        $this->resetExcept('supplier');
        $this->supplier = new Supplier();
        $this->dispatch('init-new');
    }

    public function Edit(Supplier $supplier)
    {
        $this->resetValidation();
        $this->supplier = $supplier;
        $this->editing = true;
    }

    public function cancelEdit()
    {
        $this->resetValidation();
        $this->supplier = new Supplier();
        $this->editing = false;
        $this->editing = null;
        $this->dispatch('init-new');
    }



    public function Store()
    {


        $this->rules['supplier.name'] = $this->supplier->id > 0 ? "required|max:50|unique:suppliers,name,{$this->supplier->id}" : 'required|max:50|unique:suppliers,name';

        $this->validate($this->rules, $this->messages);


        // save model
        $this->supplier->save();



        $this->dispatch('noty', msg: 'INFO DEL PROVEEDOR SE GUARDO CORRECTAMENTE');
        $this->resetExcept('supplier');
        $this->supplier = new Supplier();
    }


    public function Destroy(Supplier $supplier)
    {
        if ($supplier->products->count() > 0) {
            $this->dispatchBrowserEvent('noty-error', ['msg' => 'NO SE PUEDE ELIMINAR EL PROVEEDOR PORQUE TIENE PRODUCTOS RELACIONADOS']);
            return;
        }

        // delete record from db
        $supplier->delete();

        $this->resetPage();


        $this->dispatchBrowserEvent('noty', ['msg' => 'PROVEEDOR ELIMINADO CON ÉXITO']);
        $this->dispatchBrowserEvent('stop-loader');
    }
}
