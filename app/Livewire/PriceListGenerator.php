<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Configuration;
use App\Services\PriceCalculatorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PriceListGenerator extends Component
{
    public $customers;
    public $customerId;
    
    protected $listeners = ['selected_customer' => 'handleSelectedCustomer'];

    public function handleSelectedCustomer($id)
    {
        $this->customerId = is_array($id) ? $id['id'] : $id;
    }
    
    // New Properties for Advanced Features
    public $sellers = [];
    public $selectedSellerId;
    
    // On-the-Fly Configuration
    public $customCommission;
    public $customFreight;
    public $customExchangeDiff;
    
    // On-the-Fly Commercial Conditions
    public $customCreditDays;
    public $customUsdDiscount;
    public $customMora; // New property
    // public $customRuleDays; // Deprecated

    // public $customRulePercent; // Deprecated
    // public $customRuleType = 'discount'; // Deprecated
    public $customRules = []; // Array of ['days' => '', 'percent' => '', 'type' => 'discount']

    public $availableColumns = [
        'sku' => 'Código (SKU)',
        'name' => 'Nombre del Producto',
        'stock' => 'Existencia',
        'base_price' => 'Precio Base',
        'freight' => 'Flete',
        'commission' => 'Comisión',
        'exchange_diff' => 'Diferencial Cambiario',
        'net_price' => 'Precio Neto',
        'tax_amount' => 'IVA',
        'final_price' => 'Precio'
    ];
    public $selectedColumns = [];
    public $showInfoBlock = true;
    public $config;

    public function mount()
    {
        // Filter available columns based on active modules
        $activeModules = config('tenant.modules', []);
        
        if (!in_array('module_commissions', $activeModules)) {
            unset($this->availableColumns['commission']);
            unset($this->availableColumns['freight']);
            unset($this->availableColumns['exchange_diff']);
        }

        $this->config = Configuration::first();
        
        // Load default columns from DB or fallback
        $savedColumns = $this->config->price_list_columns;
        if ($savedColumns) {
            // Check if savedColumns is already an array or a JSON string
             if (is_string($savedColumns)) {
                $this->selectedColumns = json_decode($savedColumns, true) ?? [];
            } else {
                 $this->selectedColumns = $savedColumns;
            }
            // Default columns if nothing saved
            $this->selectedColumns = ['sku', 'name', 'final_price'];
        }

        $this->showInfoBlock = $this->config->price_list_show_info_block ?? true;

        // Sanitize selected columns to ensure they are actually available (respecting module conditions)
        $this->selectedColumns = array_values(array_intersect($this->selectedColumns, array_keys($this->availableColumns)));

        // Load Customers and Sellers based on User Role
        $user = Auth::user();
        if ($user->can('sales.configure_price_list')) {
             $this->sellers = \App\Models\User::sellers()->orderBy('name')->get();
             $this->customers = Customer::orderBy('name')->get();
        } else {
            $this->customers = Customer::where('seller_id', $user->id)->orderBy('name')->get();
        }

        // Initialize with one empty rule
        $this->customRules = [
            ['days' => '', 'percent' => '', 'type' => 'discount']
        ];
    }

    public function addCustomRule()
    {
        $this->customRules[] = ['days' => '', 'percent' => '', 'type' => 'discount'];
    }

    public function removeCustomRule($index)
    {
        unset($this->customRules[$index]);
        $this->customRules = array_values($this->customRules); // Re-index
    }

    public function updatedSelectedSellerId($value)
    {
        if ($value) {
            $this->customers = Customer::where('seller_id', $value)->orderBy('name')->get();
        } else {
            $this->customers = Customer::orderBy('name')->get();
        }
        $this->customerId = null; // Reset selected customer
    }

    public function updatedSelectedColumns()
    {
        // Real-time update logic if needed
    }

    public function saveConfig()
    {
        if (!Auth::user()->can('sales.configure_price_list')) {
            $this->dispatch('noty', msg: 'No tiene permisos para guardar la configuración.', type: 'error');
            return;
        }

        $this->config->price_list_columns = $this->selectedColumns;
        $this->config->price_list_show_info_block = $this->showInfoBlock;
        $this->config->save();

        $this->dispatch('noty', msg: 'Configuración de columnas guardada correctamente.');
    }

    public function generate()
    {
        $user = Auth::user();
        $customer = null;
        $sellerConfig = null;

        if ($this->customerId) {
            $customer = Customer::find($this->customerId);
        }

        \Illuminate\Support\Facades\Log::info('Generating Price List', [
            'selectedSellerId' => $this->selectedSellerId,
            'customerId' => $this->customerId,
            'customerFound' => $customer ? $customer->name : 'None',
            'customConfig' => [
                'comm' => $this->customCommission,
                'freight' => $this->customFreight,
                'diff' => $this->customExchangeDiff
            ],
            'customConditions' => [
                'credit_days' => $this->customCreditDays,
                'usd_discount' => $this->customUsdDiscount,
                'rules' => $this->customRules,
            ]
        ]);

        // Logic Hierarchy:
        // 1. On-the-Fly Custom Values (Highest Priority)
        // 2. Selected Seller (Admin Only)
        // 3. Customer's Assigned Seller
        // 4. Current Logged-in Seller
        
        // Check for Custom Configuration
        if ($this->customCommission !== null || $this->customFreight !== null || $this->customExchangeDiff !== null) {
            // Create a temporary object mocking SellerConfig
            $sellerConfig = new \stdClass();
            $sellerConfig->commission_percent = $this->customCommission ?? 0;
            $sellerConfig->freight_percent = $this->customFreight ?? 0;
            $sellerConfig->exchange_diff_percent = $this->customExchangeDiff ?? 0;
            Log::info('Using Custom Config', (array)$sellerConfig);
        } 
        elseif ($this->selectedSellerId) {
             $seller = \App\Models\User::find($this->selectedSellerId);
             if ($seller) {
                 $sellerConfig = $seller->latestSellerConfig; 
                 Log::info('Using Selected Seller Config', ['seller' => $seller->name, 'config' => $sellerConfig]);
             }
        }
        elseif ($customer && $customer->seller_id) {
            $seller = \App\Models\User::find($customer->seller_id);
            if ($seller) {
                $sellerConfig = $seller->latestSellerConfig; 
                Log::info('Using Customer Seller Config', ['seller' => $seller->name, 'config' => $sellerConfig]);
            }
        } 
        
        if (!isset($sellerConfig) || !$sellerConfig) {
             // Fallback to current user's config
             $sellerConfig = $user->latestSellerConfig;
             Log::info('Using Fallback User Config', ['user' => $user->name, 'config' => $sellerConfig]);
        }

        $products = Product::where('status', 'available')
            ->with('category')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->orderBy('categories.name')
            ->orderBy('products.name')
            ->select('products.*', 'categories.name as category_name')
            ->get();

        $calculator = new PriceCalculatorService();
        $groupedData = [];

        foreach ($products as $product) {
            $pricing = $calculator->calculate($product, $sellerConfig, $customer);
            
            // Filter fields based on selection
            $row = [];
            // Add metadata for styling
            $row['is_out_of_stock'] = $product->stock_qty <= 0;

            foreach ($this->selectedColumns as $col) {
                switch ($col) {
                    case 'sku': $row['sku'] = $product->sku; break;
                    case 'name': $row['name'] = $product->name; break;
                    case 'stock': 
                         // Stock Logic
                         if ($product->stock_qty <= 0) {
                             $row['stock'] = 'AGOTADO';
                         } else {
                             $row['stock'] = $product->stock_qty; 
                         }
                         break;
                    default:
                        if (isset($pricing[$col])) {
                            $row[$col] = $pricing[$col];
                        }
                        break;
                }
            }
            // Group by Category
            $categoryName = $product->category ? $product->category->name : 'Sin Categoría';
             $groupedData[$categoryName][] = $row;
        }

        // Prepare Header Data
        $headerData = [
            'seller_name' => '',
            'seller_phone' => '',
            'customer_name' => $customer ? $customer->name : 'Cliente General',
            'credit_days' => 'N/A',
            'credit_limit' => 'N/A',
            'discount_rules' => [],
            'usd_discount' => 0,
        ];

        // Determine Effective Seller for Contact Info
        $effectiveSeller = null;
        if ($this->selectedSellerId) {
            $effectiveSeller = \App\Models\User::find($this->selectedSellerId);
        } elseif ($customer && $customer->seller_id) {
            $effectiveSeller = \App\Models\User::find($customer->seller_id);
        } else {
             // Fallback to current user if they are a seller
             if ($user->can('system.is_seller') || $user->can('system.is_foreign_seller')) {
                 $effectiveSeller = $user;
             }
        }

        if ($effectiveSeller) {
            $headerData['seller_name'] = $effectiveSeller->name;
            // Try to get phone from profile or fallback
            // Assuming profile is a JSON field cast to array
            $headerData['seller_phone'] = $effectiveSeller->profile_photo_path; // Using profile_photo_path as placeholder? No, check migration.
            // Actually User model doesn't show phone. Let's check if 'phone' is in profile array or separate.
            // For now, leave empty or try to find a phone field. 
            // Looking at User model, there is no phone. Let's assume it might be in 'profile' if it was added there, or just name for now.
             $headerData['seller_phone'] = $effectiveSeller->phone ?? ''; 
        }

        // Get Credit/Discount Configuration
        // Use CreditConfigService
        // We need a customer object. If no customer selected, create a dummy or use defaults?
        // If no customer, we can't really show specific credit rules unless we show the SELLER'S default rules.
        
        $dummyCustomer = $customer ?? new Customer(['allow_credit' => false]); // Dummy
        
        // We need to resolve rules based on the *effective seller* we determined above, NOT just the logged in one.
        $creditConfig = \App\Services\CreditConfigService::getCreditConfig($dummyCustomer, $effectiveSeller);

        $headerData['credit_days'] = $creditConfig['credit_days'];
        $headerData['credit_limit'] = $creditConfig['credit_limit'];
        $headerData['discount_rules'] = $creditConfig['discount_rules'];
        $headerData['usd_discount'] = $creditConfig['usd_payment_discount'];

        // --- OVERRIDE WITH ON-THE-FLY CONFIG VALUES ---
        if ($this->customCreditDays !== null && $this->customCreditDays !== '') {
            $headerData['credit_days'] = $this->customCreditDays;
        }
        
        if ($this->customUsdDiscount !== null && $this->customUsdDiscount !== '') {
            $headerData['usd_discount'] = $this->customUsdDiscount;
        }

        // Process Multiple Custom Rules
        $headerData['discount_rules'] = []; // Reset rules if custom ones are being applied
        $hasCustomRules = false;

        foreach ($this->customRules as $rule) {
            if (isset($rule['days']) && $rule['days'] !== '' && isset($rule['percent']) && $rule['percent'] !== '') {
                $customRule = new \stdClass();
                $customRule->days_from = 0; // Simplified for "0 to X days"
                $customRule->days_to = $rule['days'];
                $customRule->discount_percentage = $rule['percent'];
                $customRule->rule_type = $rule['type'] ?? 'discount';
                
                $headerData['discount_rules'][] = $customRule;
                $hasCustomRules = true;
            }
        }

        // If no custom rules were valid/added, we might want to revert to default? 
        // Logic: specific requirements said "on the fly" overrides. 
        // If the user adds rows but leaves them empty, we assume they want NO rules or failed to config.
        // If the array is empty (user removed all rows), we probably shouldn't override?
        // Let's stick to: if customRules has any valid entry, use ONLY those. 
        // If customRules is empty or has no valid entries, fall back?
        // Actually, previous behavior was: if values are set, override.
        // Let's simplify: If the user engaged with the custom rules section (which initializes with 1 empty row),
        // we should probably check if they actually filled anything.
        if (!$hasCustomRules) {
             // If no custom rules defined, revert to customer's rules?
             // Or should we allow clearing rules by sending empty?
             // For now, let's say if no valid custom rule found, keep customer rules ONLY IF user didn't try to add one.
             // But simpler: just check $creditConfig again if needed.
             // However, to keep it consistent with "Override", if no valid custom rule is provided, we restore the original rules.
             $headerData['discount_rules'] = $creditConfig['discount_rules'];
        }

        // ----------------------------------------------

        // ----------------------------------------------

        // Generate Footer Code
        $date = now()->format('d/m/Y');
        $footerCode = $this->generateFooterCode($effectiveSeller, $customer, $sellerConfig, $headerData);

        $columns = $this->selectedColumns;
        $columnLabels = $this->availableColumns;
        $showInfoBlock = $this->showInfoBlock;

        $pdf = Pdf::loadView('pdf.price-list', compact('groupedData', 'date', 'headerData', 'footerCode', 'customer', 'columns', 'columnLabels', 'showInfoBlock'));
        
        $filename = "Lista_Precios_" . now()->format('d-m-Y') . ".pdf";

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    private function generateFooterCode($seller, $customer, $sellerConfig, $headerData)
    {
        // Check for Customer Config
        $customerConfig = null;
        if ($customer && $customer->latestCustomerConfig) {
            $customerConfig = $customer->latestCustomerConfig;
        }

        // Resolve Values
        // Freight
        $freightPercent = 0;
        if ($this->customFreight !== null && $this->customFreight !== '') {
            $freightPercent = floatval($this->customFreight);
        } elseif ($customerConfig && $customerConfig->freight_percent > 0) {
            $freightPercent = floatval($customerConfig->freight_percent);
        } elseif ($sellerConfig) {
            $freightPercent = floatval($sellerConfig->freight_percent ?? 0);
        }

        // Commission
        $commPercent = 0;
        if ($this->customCommission !== null && $this->customCommission !== '') {
            $commPercent = floatval($this->customCommission);
        } elseif ($customerConfig && $customerConfig->commission_percent > 0) {
            $commPercent = floatval($customerConfig->commission_percent);
        } elseif ($sellerConfig) {
            $commPercent = floatval($sellerConfig->commission_percent ?? 0);
        }

        // Exchange Diff
        $diffPercent = 0;
        if ($this->customExchangeDiff !== null && $this->customExchangeDiff !== '') {
            $diffPercent = floatval($this->customExchangeDiff);
        } elseif ($customerConfig && $customerConfig->exchange_diff_percent > 0) {
            $diffPercent = floatval($customerConfig->exchange_diff_percent);
        } elseif ($sellerConfig) {
            $diffPercent = floatval($sellerConfig->exchange_diff_percent ?? 0);
        }

        // USD Discount
        $usdDiscount = floatval($headerData['usd_discount']);

        // Mora
        $moraPercent = 0;
        if ($this->customMora !== null && $this->customMora !== '') {
            $moraPercent = floatval($this->customMora);
        }

        // Operator
        $operator = \Illuminate\Support\Facades\Auth::user();

        return \App\Services\FooterCodeService::generate(
            $seller ? $seller->name : '',
            $customer ? $customer->name : '',
            $freightPercent,
            $commPercent,
            $diffPercent,
            'FC0000', // Invoice Placeholder
            'ES0C00', // Estimated
            $usdDiscount,
            $headerData['discount_rules'] ?? [],
            $moraPercent,
            intval($headerData['credit_days']),
            $operator->name
        );
    }

    public function render()
    {
        return view('livewire.price-list-generator')
            ->extends('layouts.theme.app')
            ->section('content');
    }
}
