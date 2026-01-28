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
    public $tab = 1; // Active tab (1=General, 2=Commercial, 3=Sales History, 4=Credit Config)
    public $customerCommission1Threshold, $customerCommission1Percentage, $customerCommission2Threshold, $customerCommission2Percentage;
    public $discountRules = []; // Array of discount rules for this customer

    protected $rules = [
        'customer.name' => 'required|max:45|unique:customers,name',
        'customer.taxpayer_id' => 'required|max:45',
        'customer.address' => 'required|max:255',
        'customer.city' => 'required|max:100',
        'customer.phone' => 'nullable|max:15',
        'customer.email' => 'nullable|email|max:65',
        'customer.type' => 'required|in:Mayoristas,Consumidor Final,Descuento1,Descuento2,Otro',
        'customer.seller_id' => 'nullable',
        'customer.allow_credit' => 'nullable|boolean',
        'customer.credit_days' => 'nullable|integer|min:1',
        'customer.credit_limit' => 'nullable|numeric|min:0',
        'customer.usd_payment_discount' => 'nullable|numeric|min:0|max:100',
        'customerCommission1Threshold' => 'nullable|numeric',
        'customerCommission1Percentage' => 'nullable|numeric',
        'customerCommission2Threshold' => 'nullable|numeric',
        'customerCommission2Percentage' => 'nullable|numeric',
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
        $this->resetCommissionFields();
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
        $this->tab = 1; // Reset to first tab
        $this->resetCommissionFields();
        $this->dispatch('init-new');
    }

    public function Edit(Customer $customer)
    {
        $this->resetValidation();
        $this->customer = $customer;
        
        $this->customerCommission1Threshold = $customer->customer_commission_1_threshold;
        $this->customerCommission1Percentage = $customer->customer_commission_1_percentage;
        $this->customerCommission2Threshold = $customer->customer_commission_2_threshold;
        $this->customerCommission2Percentage = $customer->customer_commission_2_percentage;

        // Load discount rules
        $this->loadDiscountRules();

        $this->tab = 1; // Reset to first tab
        $this->editing = true;
    }

    public function cancelEdit()
    {
        $this->resetValidation();
        $this->customer = new Customer();
        $this->editing = false;
        $this->search = null;
        $this->resetCommissionFields();
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

        $this->customer->customer_commission_1_threshold = $this->customerCommission1Threshold;
        $this->customer->customer_commission_1_percentage = $this->customerCommission1Percentage;
        $this->customer->customer_commission_2_threshold = $this->customerCommission2Threshold;
        $this->customer->customer_commission_2_percentage = $this->customerCommission2Percentage;

        // Ensure credit fields are saved (checkboxes need explicit handling)
        // If allow_credit is not set, it means checkbox is unchecked, so set to false
        if (!isset($this->customer->allow_credit)) {
            $this->customer->allow_credit = false;
        }

        // save model
        $this->customer->save();

        // Save discount rules
        $this->saveDiscountRules();

        $this->dispatch('noty', msg: 'CLIENTE SE GUARDO CORRECTAMENTE');
        
        // If editing, keep the customer loaded and preserve the tab
        if ($this->customer->id > 0) {
            $currentTab = $this->tab;
            $customerId = $this->customer->id;
            
            // Reload the customer to get fresh data
            $this->customer = Customer::find($customerId);
            
            // Reload commission fields
            $this->customerCommission1Threshold = $this->customer->customer_commission_1_threshold;
            $this->customerCommission1Percentage = $this->customer->customer_commission_1_percentage;
            $this->customerCommission2Threshold = $this->customer->customer_commission_2_threshold;
            $this->customerCommission2Percentage = $this->customer->customer_commission_2_percentage;
            
            // Reload discount rules
            $this->loadDiscountRules();
            
            // Preserve the tab
            $this->tab = $currentTab;
            $this->editing = true;
        } else {
            // If creating new, reset everything
            $this->resetExcept('customer');
            $this->customer = new Customer();
            $this->customer->type = 0;
            $this->customer->seller_id = 0;
            $this->resetCommissionFields();
            $this->tab = 1;
        }
    }

    public function resetCommissionFields()
    {
        $this->customerCommission1Threshold = null;
        $this->customerCommission1Percentage = null;
        $this->customerCommission2Threshold = null;
        $this->customerCommission2Percentage = null;
    }

    // Discount Rules Management
    public function addDiscountRule()
    {
        $this->discountRules[] = [
            'days_from' => 0,
            'days_to' => null,
            'discount_percentage' => 0,
            'rule_type' => 'early_payment',
            'description' => ''
        ];
    }

    public function removeDiscountRule($index)
    {
        unset($this->discountRules[$index]);
        $this->discountRules = array_values($this->discountRules); // Re-index array
    }

    public function loadDiscountRules()
    {
        if ($this->customer->id) {
            $rules = \App\Models\CreditDiscountRule::where('entity_type', 'customer')
                ->where('entity_id', $this->customer->id)
                ->orderBy('days_from')
                ->get();

            $this->discountRules = $rules->map(function($rule) {
                return [
                    'id' => $rule->id,
                    'days_from' => $rule->days_from,
                    'days_to' => $rule->days_to,
                    'discount_percentage' => $rule->discount_percentage,
                    'rule_type' => $rule->rule_type,
                    'description' => $rule->description
                ];
            })->toArray();
        } else {
            $this->discountRules = [];
        }
    }

    public function saveDiscountRules()
    {
        if (!$this->customer->id) {
            return;
        }

        // Delete existing rules for this customer
        \App\Models\CreditDiscountRule::where('entity_type', 'customer')
            ->where('entity_id', $this->customer->id)
            ->delete();

        // Save new rules
        foreach ($this->discountRules as $rule) {
            if (isset($rule['days_from']) && isset($rule['discount_percentage'])) {
                \App\Models\CreditDiscountRule::create([
                    'entity_type' => 'customer',
                    'entity_id' => $this->customer->id,
                    'days_from' => $rule['days_from'],
                    'days_to' => $rule['days_to'],
                    'discount_percentage' => $rule['discount_percentage'],
                    'rule_type' => $rule['rule_type'],
                    'description' => $rule['description']
                ]);
            }
        }
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
