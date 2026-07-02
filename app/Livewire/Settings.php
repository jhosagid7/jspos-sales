<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Configuration;
use Illuminate\Support\Facades\DB;

class Settings extends Component
{
    use \Livewire\WithFileUploads;

    public $setting_id = 0, $businessName, $phone, $taxpayerId, $vat, $printerName, $website, $leyend, $creditDays = 15, $address, $city, $creditPurchaseDays, $confirmationCode, $decimals;
    public $checkStockReservation, $salesViewMode, $salesEditTimeout;
    public $globalCommission1Threshold, $globalCommission1Percentage, $globalCommission2Threshold, $globalCommission2Percentage;
    public $globalAllowCredit, $globalCreditDays, $globalCreditLimit, $globalUsdPaymentDiscount, $globalUsdPaymentDiscountTag;
    public $enableSharedCashRegister; // Nuevo: Caja Compartida
    public $sequentialCutOffDate;
    public $catalogueShowPrices, $catalogueShowBasePrices;
    public $discountRules = [];

    public $logo, $logo_preview; // Logo properties
    public $backupEmails; // Backup Emails
    public $purchasingCalculationMode, $purchasingCoverageDays; // Purchasing Intelligence
    public $productionEmailRecipients, $productionEmailSubject, $productionEmailBody; // Production Email Settings
    public $sopladosEmailRecipients, $sopladosEmailSubject, $sopladosEmailBody; // Soplados Email Settings
    
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
    public $sopladosWarehouseId, $bolsasWarehouseId, $productionMaterialsWarehouseId;
    public $warehouses = [];

    // Global Rates
    public $bcvRate;
    public $binanceRate;
    public $binanceMarkupPoints = 0;
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
            $this->sequentialCutOffDate = $config->sequential_cut_off_date ? \Carbon\Carbon::parse($config->sequential_cut_off_date)->format('Y-m-d\TH:i') : null;
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
            $this->sopladosWarehouseId = $config->soplados_warehouse_id;
        $this->bolsasWarehouseId = $config->bolsas_warehouse_id;
        $this->productionMaterialsWarehouseId = $config->production_materials_warehouse_id;
            
            // Convert seconds to HH:MM:SS
            $seconds = $config->sales_edit_timeout ?? 1800; // default 30 min
            $this->salesEditTimeout = sprintf('%02d:%02d:%02d', ($seconds / 3600), ($seconds / 60 % 60), $seconds % 60);
            
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
            $this->productionEmailSubject = $config->production_email_subject ?: '[SALUDO], Reporte Diario de Producción - [FECHA] (Lote #[PRODUCCION_ID]) - [EMPRESA]';
            $this->productionEmailBody = $config->production_email_body ?: "[SALUDO],\n\nAdjunto a este correo electrónico se encuentra el reporte oficial detallado correspondiente a la jornada de producción del [FECHA].\n\nA continuación, se presenta un resumen de los lotes procesados y consolidados durante este turno:\n\n==================================================\n📝 DATOS GENERALES DE LA ORDEN DE TRABAJO\n==================================================\n• Lote de Producción: #[PRODUCCION_ID]\n• Fecha de Cierre: [FECHA]\n• Operador a Cargo del Reporte: [USUARIO]\n• Empresa / Planta: [EMPRESA]\n\n==================================================\n📊 TOTALES DE PLANTA\n==================================================\n• Cantidad Total Producida: [CANTIDAD_TOTAL] unidades\n• Peso Total de Material Procesado: [PESO_TOTAL] Kg\n\n==================================================\n📦 DESGLOSE POR PRODUCTO Y TIPO DE MATERIAL\n==================================================\n[RESUMEN_DETALLES]\n\n*(El detalle técnico por bobina individual, tipo de resina (Original/Recuperado), y mermas de extrusión y soplado se encuentra desglosado en el PDF adjunto).*\n\n==================================================\n🔍 OBSERVACIONES Y EVENTUALIDADES DE JORNADA\n==================================================\n[NOTA]\n\n--------------------------------------------------\nEste es un reporte automático emitido por el Sistema de Control de Producción y Ventas de [EMPRESA].\n\nQuedamos atentos a cualquier consulta técnica o administrativa.\n\nAtentamente,\nDepartamento de Control de Calidad y Manufactura\n[EMPRESA]";

            // Soplados Email Settings
            $this->sopladosEmailRecipients = is_array($config->soplados_email_recipients) ? implode(', ', $config->soplados_email_recipients) : $config->soplados_email_recipients;
            $this->sopladosEmailSubject = $config->soplados_email_subject ?: '[SALUDO], Reporte del Turno de Soplado - [FECHA] ([TIPO_TURNO]) - [EMPRESA]';
            $this->sopladosEmailBody = $config->soplados_email_body ?: "[SALUDO],\n\nAdjunto a este correo electrónico se encuentra el reporte oficial correspondiente al cierre del turno de soplado y manufactura de botellones/envases del [FECHA].\n\nA continuación, se presenta un resumen de los resultados del turno:\n\n==================================================\n📝 DATOS GENERALES DEL TURNO\n==================================================\n• Tipo de Turno: [TIPO_TURNO]\n• Horario del Turno: [HORA_INICIO] a [HORA_FIN]\n• Planta / Almacén: [ALMACEN]\n• Operadores del Turno: [OPERADORES]\n• Empresa: [EMPRESA]\n\n==================================================\n📊 TOTALES Y RENDIMIENTO DEL TURNO\n==================================================\n• Total Producido (1ra y 2da Calidad): [BUENA_CANTIDAD] unidades\n• Unidades Defectuosas (Merma/Desecho): [DESECHADA_CANTIDAD] unidades\n• Total Procesado (Buena + Defectuosa): [TOTAL_PRODUCIDO] unidades\n• Eficiencia del Turno (Yield): [EFICIENCIA]%\n\n==================================================\n📦 DETALLE DE ENVASES SOPLADOS (1RA Y 2DA CALIDAD)\n==================================================\n[RESUMEN_PRODUCCION]\n\n==================================================\n⚙️ MATERIALES Y MATERIA PRIMA CONSUMIDA\n==================================================\n[RESUMEN_MATERIALES]\n\n==================================================\n🔍 OBSERVACIONES / EVENTUALIDADES DEL TURNO\n==================================================\n[NOTA]\n\n--------------------------------------------------\nEste es un reporte automático de manufactura de Soplados emitido por [EMPRESA].\n\nQuedamos atentos a cualquier consulta técnica o administrativa.\n\nAtentamente,\nDepartamento de Control de Calidad y Soplado\n[EMPRESA]";

            // License Emails
            $this->licenseNotificationEmail = $config->license_notification_email;
            $this->licenseRequestEmail = $config->license_request_email;

            // Global Credit Config
            $this->globalAllowCredit = (bool) $config->global_allow_credit;
            $this->globalCreditDays = $config->global_credit_days;
            $this->globalCreditLimit = $config->global_credit_limit;
            $this->globalUsdPaymentDiscount = $config->global_usd_payment_discount;
            $this->globalUsdPaymentDiscountTag = $config->global_usd_payment_discount_tag ?? 'PD';
            $this->enableSharedCashRegister = (bool) $config->enable_shared_cash_register;
            $this->catalogueShowPrices = (bool) $config->catalogue_show_prices;
            $this->catalogueShowBasePrices = (bool) $config->catalogue_show_base_prices;
            
            // Global Rates
            $this->bcvRate = $config->bcv_rate;
            $this->binanceRate = $config->binance_rate;
            $this->binanceMarkupPoints = $config->binance_markup_points ?? 0;

            
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
        if (!empty($this->sequentialCutOffDate)) {
            try {
                \Carbon\Carbon::parse($this->sequentialCutOffDate);
            } catch (\Exception $e) {
                $this->addError('sequentialCutOffDate', 'Ingresa una fecha de corte válida!');
            }
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
                'soplados_warehouse_id' => $this->sopladosWarehouseId,
            'bolsas_warehouse_id' => $this->bolsasWarehouseId,
            'production_materials_warehouse_id' => $this->productionMaterialsWarehouseId,
                'sales_edit_timeout' => $this->convertToSeconds($this->salesEditTimeout),
                'backup_emails' => $backupEmailsArray,
                'purchasing_calculation_mode' => $this->purchasingCalculationMode,
                'purchasing_coverage_days' => intval($this->purchasingCoverageDays),
                'production_email_recipients' => array_filter(array_map('trim', explode(',', $this->productionEmailRecipients))),
                'production_email_subject' => trim($this->productionEmailSubject),
                'production_email_body' => trim($this->productionEmailBody),
                'soplados_email_recipients' => array_filter(array_map('trim', explode(',', $this->sopladosEmailRecipients))),
                'soplados_email_subject' => trim($this->sopladosEmailSubject),
                'soplados_email_body' => trim($this->sopladosEmailBody),
                'is_network' => $this->isNetwork ? 1 : 0,
                'printer_user' => $this->isNetwork ? trim($this->printerUser) : null,
                'printer_password' => $this->isNetwork ? trim($this->printerPassword) : null,
                'license_notification_email' => trim($this->licenseNotificationEmail),
                'license_request_email' => trim($this->licenseRequestEmail),
                'enable_shared_cash_register' => $this->enableSharedCashRegister ? 1 : 0,
                'catalogue_show_prices' => $this->catalogueShowPrices ? 1 : 0,
                'catalogue_show_base_prices' => $this->catalogueShowBasePrices ? 1 : 0,
                'sequential_cut_off_date' => $this->sequentialCutOffDate ? \Carbon\Carbon::parse($this->sequentialCutOffDate)->format('Y-m-d H:i:s') : null,
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
                'global_usd_payment_discount' => $this->globalUsdPaymentDiscount,
                'global_usd_payment_discount_tag' => $this->globalUsdPaymentDiscountTag ?? 'PD',
                'bcv_rate' => $this->bcvRate,
                'binance_rate' => $this->binanceRate,
                'binance_markup_points' => $this->binanceMarkupPoints,
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
    public $newBankAccountNumber;
    public $newBankCedula;
    public $newBankPhone;
    public $account_holder;
    public $selectedBankId = null; // Para rastrear si estamos editando

    public function loadBanks()
    {
        $this->banks = \App\Models\Bank::orderBy('sort')->get();
    }

    public function editBank($id)
    {
        $bank = \App\Models\Bank::find($id);
        if ($bank) {
            $this->selectedBankId = $bank->id;
            $this->newBankName = $bank->name;
            $this->newBankCurrency = $bank->currency_code;
            $this->newBankAccountNumber = $bank->account_number;
            $this->newBankCedula = $bank->cedula;
            $this->newBankPhone = $bank->phone;
            $this->account_holder = $bank->account_holder;
            
            $this->dispatch('noty', msg: 'Datos del banco cargados para editar');
        }
    }

    public function resetBankForm()
    {
        $this->reset(['newBankName', 'newBankCurrency', 'newBankAccountNumber', 'newBankCedula', 'newBankPhone', 'account_holder', 'selectedBankId']);
        $this->resetValidation();
    }

    public function addBank()
    {
        $this->validate([
            'newBankName' => 'required|string|max:255',
            'newBankCurrency' => 'required|string|max:3',
            'newBankAccountNumber' => 'required|string|max:255',
            'newBankCedula' => 'required|string|max:255',
            'newBankPhone' => 'required|string|max:255',
            'account_holder' => 'required|string|max:255',
        ], [
            'newBankAccountNumber.required' => 'El número de cuenta es obligatorio',
            'newBankCedula.required' => 'La cédula es obligatoria',
            'newBankPhone.required' => 'El teléfono es obligatorio',
            'account_holder.required' => 'El titular es obligatorio',
        ]);

        if ($this->selectedBankId) {
            $bank = \App\Models\Bank::find($this->selectedBankId);
            $bank->update([
                'name' => strtoupper($this->newBankName),
                'currency_code' => $this->newBankCurrency,
                'account_number' => $this->newBankAccountNumber,
                'cedula' => $this->newBankCedula,
                'phone' => $this->newBankPhone,
                'account_holder' => $this->account_holder,
            ]);
            $msg = 'Banco actualizado con éxito.';
        } else {
            \App\Models\Bank::create([
                'name' => strtoupper($this->newBankName),
                'currency_code' => $this->newBankCurrency,
                'account_number' => $this->newBankAccountNumber,
                'cedula' => $this->newBankCedula,
                'phone' => $this->newBankPhone,
                'account_holder' => $this->account_holder,
                'sort' => \App\Models\Bank::count() + 1,
                'state' => 1
            ]);
            $msg = 'Banco agregado con éxito.';
        }

        $this->resetBankForm();
        $this->loadBanks();
        $this->dispatch('noty', msg: $msg);
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
            'tag' => '',
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
                    'tag' => $rule->tag,
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
                    'tag' => $rule['tag'] ?? null,
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
                'binanceRate' => 'nullable|numeric|min:0',
                'binanceMarkupPoints' => 'nullable|numeric|min:0'
            ]);

            $config = Configuration::find($this->setting_id);
            if (!$config) $config = Configuration::first();

            $userId = auth()->id();
            $period = now()->hour < 12 ? 'AM' : 'PM';
            
            // Calculate Inflated Rate
            $inflatedRate = floatval($this->binanceRate) + floatval($this->binanceMarkupPoints);

            // BCV rate logging
            if ($this->bcvRate != $config->bcv_rate) {
                 if ($this->bcvRate > 0) {
                      \App\Models\ExchangeRateHistory::create([
                          'rate_type' => 'BCV',
                          'rate' => $this->bcvRate,
                          'period' => $period,
                          'user_id' => $userId
                      ]);
                 }
            }

            // Binance rate logging
            if ($this->binanceRate != $config->binance_rate || $this->binanceMarkupPoints != $config->binance_markup_points) {
                 if ($this->binanceRate > 0) {
                      // Log real Binance rate input
                      \App\Models\ExchangeRateHistory::create([
                          'rate_type' => 'BinanceReal',
                          'rate' => $this->binanceRate,
                          'period' => $period,
                          'user_id' => $userId
                      ]);

                      // Log calculated inflated Binance rate
                      \App\Models\ExchangeRateHistory::create([
                          'rate_type' => 'Binance',
                          'rate' => $inflatedRate,
                          'period' => $period,
                          'user_id' => $userId
                      ]);
                 }
            }

            // Update Config
            $config->update([
                'bcv_rate' => $this->bcvRate,
                'binance_rate' => $this->binanceRate,
                'binance_markup_points' => $this->binanceMarkupPoints
            ]);

            // Sync with VES/VED Currencies exchange rate automatically
            DB::table('currencies')
                ->whereIn('code', ['VES', 'VED'])
                ->update([
                    'exchange_rate' => $inflatedRate,
                    'updated_at' => now()
                ]);

            // Reload currencies in session
            $this->loadCurrencies();

            // Send WhatsApp notification to "Diferencial" group
            try {
                $whatsappService = app(\App\Services\WhatsappService::class);
                $status = $whatsappService->checkStatus();
                if ($status) {
                    $companyName = strtoupper($config->business_name ?: 'SISTEMA');
                    $dateStr = now()->format('d/m/Y');
                    $bcvStr = number_format(floatval($this->bcvRate), 2, '.', '');
                    $monitorStr = number_format(floatval($this->binanceRate), 2, '.', '');
                    $diffVal = floatval($this->bcvRate) > 0 ? (floatval($this->binanceRate) / floatval($this->bcvRate)) : 0;
                    // Truncate to 4 decimal places to match user's custom formatting
                    $diffVal = floor(round($diffVal, 8) * 10000) / 10000;
                    $diferencialStr = number_format($diffVal, 4, '.', '');
                    $sistemaVal = floatval($inflatedRate);
                    $sistemaStr = ($sistemaVal == intval($sistemaVal)) ? intval($sistemaVal) : number_format($sistemaVal, 2, '.', '');

                    $waMessage = "{$companyName}\n" .
                                 "{$dateStr}\n" .
                                 "BCV: {$bcvStr}\n" .
                                 "MONITOR: {$monitorStr}\n" .
                                 "DIFERENCIAL: {$diferencialStr}\n" .
                                 "SISTEMA: {$sistemaStr}";

                    $whatsappService->sendToGroupByName('Diferencial', $waMessage);
                }
            } catch (\Exception $ex) {
                \Illuminate\Support\Facades\Log::error("Error enviando tasa al grupo de WhatsApp: " . $ex->getMessage());
            }

            $this->dispatch('noty', msg: 'Tasas Globales y Ajuste actualizados correctamente');

        } catch (\Exception $e) {
            $this->dispatch('noty', msg: 'Error al guardar tasas: ' . $e->getMessage());
        }
    }

    public function viewRateHistory()
    {
        $entries = \App\Models\ExchangeRateHistory::with('user')
            ->orderBy('created_at', 'desc')
            ->take(150)
            ->get();
            
        $grouped = [];
        foreach ($entries as $e) {
            $date = $e->created_at->format('Y-m-d');
            if (!isset($grouped[$date])) {
                $grouped[$date] = [
                    'date' => $e->created_at->format('d/m/Y'),
                    'bcv' => null,
                    'binance_real_am' => null,
                    'binance_real_pm' => null,
                    'binance_inflated_am' => null,
                    'binance_inflated_pm' => null,
                    'user' => $e->user->name ?? 'Sistema'
                ];
            }
            
            if ($e->rate_type === 'BCV') {
                $grouped[$date]['bcv'] = $e->rate;
            } elseif ($e->rate_type === 'BinanceReal') {
                if ($e->period === 'AM') {
                    $grouped[$date]['binance_real_am'] = $e->rate;
                } else {
                    $grouped[$date]['binance_real_pm'] = $e->rate;
                }
            } elseif ($e->rate_type === 'Binance') {
                if ($e->period === 'AM') {
                    $grouped[$date]['binance_inflated_am'] = $e->rate;
                } else {
                    $grouped[$date]['binance_inflated_pm'] = $e->rate;
                }
            }
        }
        
        $this->historyRates = array_values($grouped);
            
        $this->dispatch('show-history-modal');
    }

    private function convertToSeconds($time)
    {
        if (is_numeric($time)) return intval($time);
        
        $parts = explode(':', $time);
        if (count($parts) == 3) {
            return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
        } else if (count($parts) == 2) {
            return ($parts[0] * 60) + $parts[1];
        }
        
        return intval($time);
    }
}

