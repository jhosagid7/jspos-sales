<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function collection()
    {
        return Product::with('category')
            ->orderBy('name')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Nombre',
            'Código de Barras / SKU',
            'Categoría',
            'Precio de Venta',
            'Costo de Compra',
            'Stock',
            'Descripción',
        ];
    }

    /**
     * @param Product $product
     */
    public function map($product): array
    {
        return [
            $product->name,
            $product->sku,
            $product->category?->name ?? 'General',
            number_format((float)$product->price, 2, '.', ''),
            number_format((float)$product->cost, 2, '.', ''),
            $product->stock_qty,
            $product->description ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF1E3A8A'], // Azul corporativo
                ],
            ],
        ];
    }
}
