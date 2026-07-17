<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Bank;
use App\Models\Configuration;
use App\Services\BankTreasuryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BankDailyCutoff extends Command
{
    protected $signature = 'treasury:bank-cutoff {date?}';
    protected $description = 'Perform daily bank closures for all tracked bank accounts';

    public function handle(): void
    {
        $date = $this->argument('date') ?: Carbon::today('America/Caracas')->format('Y-m-d');
        
        $this->info("Starting bank daily cutoff for date: {$date}");
        
        try {
            $config = Configuration::first();
            $autoClose = $config ? (bool) $config->treasury_auto_close : true;

            if (!$autoClose && !$this->argument('date')) {
                $this->info("Auto-close is disabled in settings. Skipping.");
                return;
            }

            $trackedBanks = Bank::tracked()->where('state', 1)->get();

            if ($trackedBanks->isEmpty()) {
                $this->info("No tracked banks found.");
                return;
            }

            foreach ($trackedBanks as $bank) {
                $this->info("Performing closure for bank: {$bank->name} ({$bank->currency_code})");
                
                BankTreasuryService::performDailyClosure($bank->id, $date);
                
                $this->info("Closure completed for bank ID {$bank->id}.");
            }

            $this->info("All bank daily closures completed successfully.");
            
        } catch (\Exception $e) {
            $this->error("Error performing bank daily cutoff: " . $e->getMessage());
            Log::error("BankDailyCutoff command error: " . $e->getMessage(), [
                'exception' => $e
            ]);
        }
    }
}
