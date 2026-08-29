<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Traits\PrintTrait;

class SellerGroupedTicketPrintTest extends TestCase
{
    use PrintTrait;

    public function test_seller_grouped_ticket_method_signature_and_execution()
    {
        $dummyData = collect([
            'Jhonny Pirela (77)' => collect([
                'LOCAL' => collect([
                    (object)[
                        'method' => 'CASH',
                        'currency' => 'USD',
                        'total_amount' => 6.39,
                        'avg_rate' => 1.0,
                        'total_usd' => 6.39
                    ]
                ]),
                'GRAVADO' => collect([
                    (object)[
                        'method' => 'CASH',
                        'currency' => 'USD',
                        'total_amount' => 20.00,
                        'avg_rate' => 1.0,
                        'total_usd' => 20.00
                    ]
                ])
            ])
        ]);

        // In test mode, getPrinterConfig returns null and handles gracefully without throwing
        $this->printSellerGroupedTicket($dummyData, '2026-08-29', '2026-08-29', true, true);
        $this->assertTrue(true);
    }
}
