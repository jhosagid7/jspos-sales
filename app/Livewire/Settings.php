<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Configuration;
use Illuminate\Support\Facades\DB;

class Settings extends Component
{
    public $setting_id = 0, $businessName, $phone, $taxpayerId, $vat, $printerName, $website, $leyend, $creditDays = 15, $address, $city, $creditPurchaseDays, $confirmationCode, $decimals;
    
    public $tab = 1; // Control de pestañas

    public $primaryCurrency; // Moneda principal
    public $availableCurrencies = ['USD', 'COP', 'VES']; // Lista de monedas disponibles
    public $currencies = []; // Lista de monedas configuradas
    public $newCurrencyCode;
    public $newCurrencyLabel;
    public $newCurrencySymbol;
    public $newExchangeRate;

    function mount()
    {
        session(['map' => 'Configuraciones', 'child' => ' Sistema ', 'pos' => 'Settings']);

        $this->loadConfig();
        $this->loadCurrencies();
        $this->loadBanks();
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
        if (empty($this->printerName)) {
            $this->addError('printerName', 'Ingresa la impresora');
        }
        if (empty($this->creditDays)) {
            $this->addError('creditDays', 'Ingresa días límite de pago');
        }
        if (!is_numeric($this->creditDays)) {
            $this->addError('creditDays', 'Ingresa los días con números');
        }
        if (count($this->getErrorBag()) > 0) {
            return;
        }


        try {
            Configuration::updateOrCreate(
                ['id' => $this->setting_id],
                [
                    'business_name' => trim($this->businessName),
                    'address' => trim($this->address),
                    'city' => trim($this->city),
                    'phone' => trim($this->phone),
                    'taxpayer_id' => trim($this->taxpayerId),
                    'vat' => trim($this->vat),
                    'decimals' => trim($this->decimals),
                    'printer_name' => trim($this->printerName),
                    'leyend' => trim($this->leyend),
                    'website' => trim($this->website),
                    'credit_days' => intval($this->creditDays),
                    'credit_purchase_days' => intval($this->creditPurchaseDays),
                    'confirmation_code' => intval($this->confirmationCode)
                ]
            );

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
}
