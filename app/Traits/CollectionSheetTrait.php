<?php

namespace App\Traits;

use App\Models\CollectionSheet;
use Carbon\Carbon;

trait CollectionSheetTrait
{
    private function getOrCreateCollectionSheet()
    {
        $today = Carbon::today();
        
        // 1. Find or Create Open Sheet for Today
        $sheet = CollectionSheet::where('status', 'open')
            ->whereDate('opened_at', $today)
            ->first();

        if (!$sheet) {
            // Create new sheet for today
            // Generate Sheet Number: YYYYMMDD-01
            $dateStr = $today->format('Ymd');
            $count = CollectionSheet::whereDate('opened_at', $today)->count() + 1;
            $sheetNumber = $dateStr . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

            $sheet = CollectionSheet::create([
                'sheet_number' => $sheetNumber,
                'status' => 'open',
                'opened_at' => Carbon::now(),
                'total_amount' => 0
            ]);
        }

        return $sheet->id;
    }
}
