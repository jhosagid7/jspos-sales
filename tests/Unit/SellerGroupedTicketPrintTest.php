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

        // Detailed Ticket with invoice counts and invoice breakdown
        $invoiceCounts = ['Jhonny Pirela (77)' => 2];
        $invoicesByOperator = [
            'Jhonny Pirela (77)' => collect([
                (object)[
                    'id' => 1,
                    'invoice_number' => 'FAC-001',
                    'customer_name' => 'Cliente A',
                    'total' => 20,
                    'total_usd' => 20,
                    'created_at' => '2026-08-29 10:00:00'
                ],
                (object)[
                    'id' => 2,
                    'invoice_number' => 'FAC-002',
                    'customer_name' => 'Cliente B',
                    'total' => 15,
                    'total_usd' => 15,
                    'created_at' => '2026-08-29 11:00:00'
                ]
            ])
        ];
        $this->printSellerGroupedTicket($dummyData, '2026-08-29', '2026-08-29', true, true, false, $invoiceCounts, $invoicesByOperator);
        $this->assertTrue(true);
    }
}
