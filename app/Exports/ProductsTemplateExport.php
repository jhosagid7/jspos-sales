<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function array(): array
    {
        return [
            [
                'Coca Cola 1.5L',
                '7701234567890',
                'Bebidas',
                '1.50',
                '1.00',
                '24',
                'Bebida gaseosa 1.5 litros',
            ],
            [
                'Harina Pan 1kg',
                '7709876543210',
                'Alimentos',
                '1.20',
                '0.90',
                '50',
                'Harina de maíz blanco precocida',
            ],
            [
                'Arroz Blanco 1kg',
                '',
                'Granos',
                '1.10',
                '0.85',
                '30',
                'Arroz blanco de primera (sin código de barras)',
            ],
            [
                'Aceite de Girasol 1L',
                '7701122334455',
                'Alimentos',
                '2.50',
                '1.90',
                '15',
                'Aceite comestible vegetal 1L',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Nombre',
            'Código de Barras',
            'Categoría',
            'Precio de Venta',
            'Costo de Compra',
            'Stock Inicial',
            'Descripción',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF0D9488'], // Verde/Teal elegante
                ],
            ],
        ];
    }
}
