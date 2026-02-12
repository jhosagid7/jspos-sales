<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Configuration;
use Illuminate\Support\Facades\DB;

class Settings extends Component
{
    use \Livewire\WithFileUploads;

    public $setting_id = 0, $businessName, $phone, $taxpayerId, $vat, $printerName, $website, $leyend, $creditDays = 15, $address, $city, $creditPurchaseDays, $confirmationCode, $decimals;
    public $checkStockReservation, $salesViewMode;
    public $globalCommission1Threshold, $globalCommission1Percentage, $globalCommission2Threshold, $globalCommission2Percentage;
    public $globalAllowCredit, $globalCreditDays, $globalCreditLimit, $globalUsdPaymentDiscount;
    public $discountRules = [];

    public $logo, $logo_preview; // Logo properties
    public $backupEmails; // Backup Emails
    public $purchasingCalculationMode, $purchasingCoverageDays; // Purchasing Intelligence
    public $productionEmailRecipients, $productionEmailSubject, $productionEmailBody; // Production Email Settings
    
    // Printer Auth
    public $isNetwork = false;
    public $printerHost, $printerShare;
    public $printerUser, $printerPassword;

    public $licenseNotificationEmail, $licenseRequestEmail;

    public $tab = 1; // Control de pestañas
    
    // ... items ...

    public $primaryCurrency; // Moneda principal
    public $availableCurrencies = ['USD', 'COP', 'VES']; // Lista de monedas disponibles
    public $currencies = []; // Lista de monedas configuradas
    public $editableRates = []; // Tasas editables
    public $newCurrencyCode;
    public $newCurrencyLabel;
    public $newCurrencySymbol;
    public $newExchangeRate;
    
    public $defaultWarehouseId;
    public $warehouses = [];

    // Global Rates
    public $bcvRate;
    public $binanceRate;
    public $historyRates = [];
    public $showHistoryModal = false;

    function mount()
    {
        session(['map' => 'Configuraciones', 'child' => ' Sistema ', 'pos' => 'Settings']);

        $this->loadConfig();
        $this->loadCurrencies();
        $this->loadBanks();
        $this->warehouses = \App\Models\Warehouse::where('is_active', 1)->get();
    }

    public function render()
    {
        return view('livewire.settings');
    }

    function loadConfig()
    {
        $config = Configuration::first();
        if ($config) {
            $this->setting_id = $config->id;
            $this->businessName = $config->business_name;
            $this->address = $config->address;
            $this->city = $config->city;
            $this->phone = $config->phone;
            $this->taxpayerId = $config->taxpayer_id;
            $this->vat = $config->vat;
            $this->decimals = $config->decimals;
            $this->printerName = $config->printer_name;
            $this->leyend = $config->leyend;
            $this->website = $config->website;
            $this->creditDays = $config->credit_days;
            $this->creditPurchaseDays = $config->credit_purchase_days;
            $this->confirmationCode = $config->confirmation_code;
            $this->globalCommission1Threshold = $config->global_commission_1_threshold;
            $this->globalCommission1Percentage = $config->global_commission_1_percentage;
            $this->globalCommission2Threshold = $config->global_commission_2_threshold;
            $this->globalCommission2Percentage = $config->global_commission_2_percentage;
            $this->logo_preview = $config->logo; // Load existing logo
            $this->checkStockReservation = (bool) $config->check_stock_reservation;
            $this->salesViewMode = $config->sales_view_mode;
            $this->defaultWarehouseId = $config->default_warehouse_id;
            
            // Network Printer
            $this->isNetwork = (bool) $config->is_network;
            $this->printerUser = $config->printer_user;
            $this->printerPassword = $config->printer_password;
            
            if ($this->isNetwork && $this->printerName) {
                // Try to parse \\HOST\SHARE
                $cleanName = str_replace('\\\\', '', $this->printerName);
                $parts = explode('\\', $cleanName);
                if (count($parts) >= 2) {
                    $this->printerHost = $parts[0];
                    $this->printerShare = $parts[1];
                } else {
                     $this->printerHost = $cleanName;
                }
            }

            // Load backup emails (array to string)
            $this->backupEmails = is_array($config->backup_emails) ? implode(', ', $config->backup_emails) : $config->backup_emails;

            // Purchasing Intelligence
            $this->purchasingCalculationMode = $config->purchasing_calculation_mode ?? 'recent';
            $this->purchasingCoverageDays = $config->purchasing_coverage_days ?? 15;

            // Production Email Settings
            $this->productionEmailRecipients = is_array($config->production_email_recipients) ? implode(', ', $config->production_email_recipients) : $config->production_email_recipients;
            $this->productionEmailSubject = $config->production_email_subject;
            $this->productionEmailBody = $config->production_email_body;

            // License Emails
            $this->licenseNotificationEmail = $config->license_notification_email;
            $this->licenseRequestEmail = $config->license_request_email;

            // Global Credit Config
            $this->globalAllowCredit = (bool) $config->global_allow_credit;
            $this->globalCreditDays = $config->global_credit_days;
            $this->globalCreditLimit = $config->global_credit_limit;
            $this->globalUsdPaymentDiscount = $config->global_usd_payment_discount;
            
            // Global Rates
            $this->bcvRate = $config->bcv_rate;
            $this->binanceRate = $config->binance_rate;

            
            // Load Discount Rules
            $this->loadDiscountRules();
        }
    }

    function saveConfig()
    {
        $this->resetValidation();


        if (empty($this->businessName)) {
            $this->addError('businessName', 'Ingresa la empresa');
        }
        if (empty($this->address)) {
            $this->addError('address', 'Ingresa la dirección');
        }
        if (empty($this->city)) {
            $this->addError('city', 'Ingresa la ciudad');
        }
        if (empty($this->taxpayerId)) {
            $this->addError('taxpayerId', 'Ingresa el RFC/RUT');
        }
        if (!is_numeric($this->vat)) {
            $this->addError('vat', 'Ingresa el IVA en números!');
        }
        if (!is_numeric($this->decimals)) {
            $this->addError('decimals', 'Ingresa el Decimales en números!');
        }
        
        // Printer Validation logic
        if (!$this->isNetwork && empty($this->printerName)) {
            $this->addError('printerName', 'Ingresa la impresora');
        }
        if ($this->isNetwork) {
             if (empty($this->printerHost)) $this->addError('printerHost', 'Ingresa la IP o Host');
             if (empty($this->printerShare)) $this->addError('printerShare', 'Ingresa el nombre compartido');
        }

        // Validate Backup Emails
        if (!empty($this->backupEmails)) {
            $emails = array_map('trim', explode(',', $this->backupEmails));
            foreach ($emails as $email) {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->addError('backupEmails', "El correo '$email' no es válido.");
                }
            }
        }

        if (empty($this->creditDays)) {
            $this->addError('creditDays', 'Ingresa días límite de pago');
        }
        if (!is_numeric($this->creditDays)) {
            $this->addError('creditDays', 'Ingresa los días con números');
        }

        // Validate Commissions
        if (!empty($this->globalCommission1Threshold) && !is_numeric($this->globalCommission1Threshold)) {
            $this->addError('globalCommission1Threshold', 'Debe ser numérico');
        }
        if (!empty($this->globalCommission1Percentage) && !is_numeric($this->globalCommission1Percentage)) {
            $this->addError('globalCommission1Percentage', 'Debe ser numérico');
        }
        if (!empty($this->globalCommission2Threshold) && !is_numeric($this->globalCommission2Threshold)) {
            $this->addError('globalCommission2Threshold', 'Debe ser numérico');
        }
        if (!empty($this->globalCommission2Percentage) && !is_numeric($this->globalCommission2Percentage)) {
            $this->addError('globalCommission2Percentage', 'Debe ser numérico');
        }
        
        // Validate Logo
        if ($this->logo) {
            $this->validate([
                'logo' => 'image|max:1024', // 1MB Max
            ]);
        }

        if (count($this->getErrorBag()) > 0) {
            $this->dispatch('noty', msg: 'Hay errores de validación. Por favor revisa todas las pestañas.');
            return;
        }

        // Permission check for stock reservation setting
        $currentConfig = Configuration::find($this->setting_id);
        if ($currentConfig && $this->checkStockReservation != $currentConfig->check_stock_reservation) {
            if (!auth()->user()->can('settings.stock_reservation')) {
                $this->addError('checkStockReservation', 'No tienes permiso para cambiar la configuración de reserva de stock.');
                return;
            }
        }


        try {
            // Process backup emails
            $backupEmailsArray = array_filter(array_map('trim', explode(',', $this->backupEmails)));
            
            // Construct printer name
            $finalPrinterName = $this->printerName;
            if ($this->isNetwork) {
                // Ensure no double backslashes unless needed (Windows UNC starts with \\)
                $host = trim($this->printerHost, '\\'); 
                $share = trim($this->printerShare, '\\');
                $finalPrinterName = "\\\\{$host}\\{$share}";
            }

            $data = [
                'business_name' => trim($this->businessName),
                'address' => trim($this->address),
                'city' => trim($this->city),
                'phone' => trim($this->phone),
                'taxpayer_id' => trim($this->taxpayerId),
                'vat' => trim($this->vat),
                'decimals' => trim($this->decimals),
                'printer_name' => $finalPrinterName,
                'leyend' => trim($this->leyend),
                'website' => trim($this->website),
                'credit_days' => intval($this->creditDays),
                'credit_purchase_days' => intval($this->creditPurchaseDays),
                'confirmation_code' => intval($this->confirmationCode),
                'global_commission_1_threshold' => $this->globalCommission1Threshold,
                'global_commission_1_percentage' => $this->globalCommission1Percentage,
                'global_commission_2_threshold' => $this->globalCommission2Threshold,
                'global_commission_2_percentage' => $this->globalCommission2Percentage,
                'check_stock_reservation' => $this->checkStockReservation ? 1 : 0,
                'sales_view_mode' => $this->salesViewMode,
                'default_warehouse_id' => $this->defaultWarehouseId,
                'backup_emails' => $backupEmailsArray,
                'purchasing_calculation_mode' => $this->purchasingCalculationMode,
                'purchasing_coverage_days' => intval($this->purchasingCoverageDays),
                'production_email_recipients' => array_filter(array_map('trim', explode(',', $this->productionEmailRecipients))),
                'production_email_subject' => trim($this->productionEmailSubject),
                'production_email_body' => trim($this->productionEmailBody),
                'is_network' => $this->isNetwork ? 1 : 0,
                'printer_user' => $this->isNetwork ? trim($this->printerUser) : null,
                'printer_password' => $this->isNetwork ? trim($this->printerPassword) : null,
                'license_notification_email' => trim($this->licenseNotificationEmail),
                'license_request_email' => trim($this->licenseRequestEmail),
            ];

            // Handle Logo Upload
            if ($this->logo) {
                $customFileName = uniqid() . '_.' . $this->logo->extension();
                $this->logo->storeAs('public/logos', $customFileName);
                $data['logo'] = 'logos/' . $customFileName;
                $this->logo_preview = $data['logo'];
            }

            Configuration::updateOrCreate(
                ['id' => $this->setting_id],
                $data
            );

            // Save Global Settings for Credit:
            // Need to update specifically because updateOrCreate might not trigger if I missed adding them to $data array above.
            // Wait, I should add them to $data array above. 
            // Let's add them to $data array to be clean.
            
            // Re-fetch to ensure ID is correct if created
            $conf = Configuration::find($this->setting_id);
            if(!$conf) $conf = Configuration::first();
            
            $conf->update([
                 'global_allow_credit' => $this->globalAllowCredit ? 1 : 0,
                 'global_credit_days' => $this->globalCreditDays,
                 'global_credit_limit' => $this->globalCreditLimit,
                 'global_usd_payment_discount' => $this->globalUsdPaymentDiscount
            ]);
            
            $this->saveDiscountRules();

            $this->loadConfig();
            $this->dispatch('noty', msg: "Configuración General Actualizada");
            //

        } catch (\Throwable $th) {
            $this->dispatch('noty', msg: "Error al intentar actualizar la configuración general: " . $th->getMessage());
        }
    }

    public function loadCurrencies()
    {
        $this->currencies = DB::table('currencies')->get();
        $this->primaryCurrency = DB::table('currencies')->where('is_primary', true)->value('code');
        
        // Cargar tasas editables
        foreach($this->currencies as $currency) {
            $this->editableRates[$currency->id] = $currency->exchange_rate;
        }
    }
    
    public function updateCurrencyRate($id)
    {
        try {
            $rate = $this->editableRates[$id] ?? null;
            
            if (!is_numeric($rate) || $rate <= 0) {
                $this->dispatch('noty', msg: 'La tasa de cambio debe ser un número mayor a 0.');
                return;
            }
            
            DB::table('currencies')->where('id', $id)->update([
                'exchange_rate' => $rate,
                'updated_at' => now()
            ]);
            
            $this->loadCurrencies();
            $this->dispatch('noty', msg: 'Tasa de cambio actualizada correctamente.');
            
        } catch (\Throwable $th) {
            $this->dispatch('noty', msg: 'Error al actualizar la tasa: ' . $th->getMessage());
        }
    }

    public function addCurrency()
    {
        $this->validate([
            'newCurrencyCode' => 'required|string|max:3',
            'newCurrencyLabel' => 'required|string|max:10',
            'newCurrencySymbol' => 'required|string|max:3',
            'newExchangeRate' => 'required|numeric|min:0.000001',
        ]);

        DB::table('currencies')->insert([
            'code' => strtoupper($this->newCurrencyCode),
            'label' => strtoupper($this->newCurrencyLabel),
            'symbol' => strtoupper($this->newCurrencySymbol),
            'name' => $this->newCurrencyCode,
            'exchange_rate' => $this->newExchangeRate,
            'is_primary' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->loadCurrencies();
        $this->dispatch('noty', msg: 'Moneda agregada con éxito.');
    }

    public function setPrimaryCurrency()
    {
        if (!$this->primaryCurrency) {
            $this->dispatch('noty', msg: 'Selecciona una moneda principal.');
            return;
        }

        // Actualizar todas las monedas a no principal
        DB::table('currencies')->update(['is_primary' => false]);

        // Establecer la moneda seleccionada como principal
        DB::table('currencies')->where('code', $this->primaryCurrency)->update(['is_primary' => true]);

        $this->dispatch('noty', msg: 'Moneda principal actualizada con éxito.');
        $this->loadCurrencies(); // Recargar las monedas
    }

    public function deleteCurrency($currencyId)
    {
        try {
            // Verificar si la moneda existe
            $currency = DB::table('currencies')->where('id', $currencyId)->first();

            if (!$currency) {
                $this->dispatch('noty', msg: 'La moneda no existe.');
                return;
            }

            // No permitir eliminar la moneda principal
            if ($currency->is_primary) {
                $this->dispatch('noty', msg: 'No puedes eliminar la moneda principal.');
                return;
            }

            // Eliminar la moneda
            DB::table('currencies')->where('id', $currencyId)->delete();

            // Recargar las monedas
            $this->loadCurrencies();

            $this->dispatch('noty', msg: 'Moneda eliminada con éxito.');
        } catch (\Throwable $th) {
            $this->dispatch('noty', msg: 'Error al intentar eliminar la moneda: ' . $th->getMessage());
        }
    }
    public $banks = [];
    public $newBankName;
    public $newBankCurrency;

    public function loadBanks()
    {
        $this->banks = \App\Models\Bank::orderBy('sort')->get();
    }

    public function addBank()
    {
        $this->validate([
            'newBankName' => 'required|string|max:255',
            'newBankCurrency' => 'required|string|max:3',
        ]);

        \App\Models\Bank::create([
            'name' => strtoupper($this->newBankName),
            'currency_code' => $this->newBankCurrency,
            'sort' => \App\Models\Bank::count() + 1,
            'state' => 1
        ]);

        $this->newBankName = '';
        $this->newBankCurrency = '';
        $this->loadBanks();
        $this->dispatch('noty', msg: 'Banco agregado con éxito.');
    }

    public function deleteBank($bankId)
    {
        try {
            \App\Models\Bank::destroy($bankId);
            $this->loadBanks();
            $this->dispatch('noty', msg: 'Banco eliminado con éxito.');
        } catch (\Throwable $th) {
            $this->dispatch('noty', msg: 'Error al eliminar banco: ' . $th->getMessage());
        }
    }

    // Discount Rules Management (Global)
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
        $this->discountRules = array_values($this->discountRules);
    }

    public function loadDiscountRules()
    {
        if ($this->setting_id) {
            $rules = \App\Models\CreditDiscountRule::where('entity_type', 'global')
                ->where('entity_id', $this->setting_id)
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
        if (!$this->setting_id) {
            return;
        }

        // Delete existing rules for global config
        \App\Models\CreditDiscountRule::where('entity_type', 'global')
            ->where('entity_id', $this->setting_id)
            ->delete();

        // Save new rules
        foreach ($this->discountRules as $rule) {
            if (isset($rule['days_from']) && isset($rule['discount_percentage'])) {
                \App\Models\CreditDiscountRule::create([
                    'entity_type' => 'global',
                    'entity_id' => $this->setting_id,
                    'days_from' => $rule['days_from'],
                    'days_to' => $rule['days_to'],
                    'discount_percentage' => $rule['discount_percentage'],
                    'rule_type' => $rule['rule_type'],
                    'description' => $rule['description'] ?? ''
                ]);
            }
        }
    }
    public function saveGlobalRates()
    {
        try {
            // Validate
            $this->validate([
                'bcvRate' => 'nullable|numeric|min:0',
                'binanceRate' => 'nullable|numeric|min:0'
            ]);

            $config = Configuration::find($this->setting_id);
            if (!$config) $config = Configuration::first();

            // Check changes and save history
            $userId = auth()->id();

            if ($this->bcvRate != $config->bcv_rate) {
                 if ($this->bcvRate > 0) {
                     \App\Models\ExchangeRateHistory::create([
                         'rate_type' => 'BCV',
                         'rate' => $this->bcvRate,
                         'user_id' => $userId
                     ]);
                 }
            }

            if ($this->binanceRate != $config->binance_rate) {
                 if ($this->binanceRate > 0) {
                     \App\Models\ExchangeRateHistory::create([
                         'rate_type' => 'Binance',
                         'rate' => $this->binanceRate,
                         'user_id' => $userId
                     ]);
                 }
            }

            // Update Config
            $config->update([
                'bcv_rate' => $this->bcvRate,
                'binance_rate' => $this->binanceRate
            ]);

            $this->dispatch('noty', msg: 'Tasas Globales actualizadas correctamente');

        } catch (\Exception $e) {
            $this->dispatch('noty', msg: 'Error al guardar tasas: ' . $e->getMessage());
        }
    }

    public function viewRateHistory()
    {
        $this->historyRates = \App\Models\ExchangeRateHistory::with('user')
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();
            
        $this->dispatch('show-history-modal');
    }
}

