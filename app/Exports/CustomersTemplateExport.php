<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomersTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function array(): array
    {
        return [
            [
                'Comercializadora San José C.A.',
                '04141234567',
                'ventas@sanjose.com',
                'Av. Bolívar #12-34',
                'Valencia',
                'J-12345678-9',
                'Mayorista',
                'OFICINA',
            ],
            [
                'Juan Pérez',
                '04249876543',
                'juanperez@gmail.com',
                'Calle Miranda Casa 45',
                'Caracas',
                'V-18765432',
                'Minorista',
                'OFICINA',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Nombre',
            'Teléfono',
            'Correo Electrónico',
            'Dirección',
            'Ciudad',
            'RUT / DNI / ID',
            'Tipo de Cliente',
            'Vendedor',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF0D9488'],
                ],
            ],
        ];
    }
}
