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
                        'total_amount' => 20.00,
                        'avg_rate' => 1.0,
                        'total_usd' => 20.00
                    ],
                    (object)[
                        'method' => 'PAGO MOVIL',
                        'currency' => 'VES',
                        'total_amount' => 1450.50,
                        'avg_rate' => 72.525,
                        'total_usd' => 20.00
                    ]
                ]),
                'GRAVADO' => collect([
                    (object)[
                        'method' => 'CASH',
                        'currency' => 'USD',
                        'total_amount' => 15.00,
                        'avg_rate' => 1.0,
                        'total_usd' => 15.00
                    ],
                    (object)[
                        'method' => 'TRANSFERENCIA',
                        'currency' => 'VES',
                        'total_amount' => 725.25,
                        'avg_rate' => 72.525,
                        'total_usd' => 10.00
                    ]
                ])
            ])
        ]);

        // Standard Detailed Ticket
        $this->printSellerGroupedTicket($dummyData, '2026-08-29', '2026-08-29', true, true, false);
        $this->assertTrue(true);

        // Condensed Summary Ticket
        $this->printSellerGroupedTicket($dummyData, '2026-08-29', '2026-08-29', true, true, true);
        $this->assertTrue(true);
    }
}
