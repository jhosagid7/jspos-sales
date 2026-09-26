<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return Customer::with('seller')
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Nombre / Razón Social',
            'Teléfono',
            'Correo Electrónico',
            'Dirección',
            'Ciudad',
            'RUT / DNI / ID',
            'Tipo de Cliente',
            'Vendedor Asignado',
        ];
    }

    /**
     * @param Customer $customer
     */
    public function map($customer): array
    {
        return [
            $customer->name,
            $customer->phone ?? '',
            $customer->email ?? '',
            $customer->address ?? '',
            $customer->city ?? '',
            $customer->taxpayer_id ?? '',
            $customer->type ?? 'Minorista',
            $customer->seller?->name ?? 'OFICINA',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E3A8A'],
                ],
            ],
        ];
    }
}
