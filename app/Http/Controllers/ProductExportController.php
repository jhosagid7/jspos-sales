<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Exports\ProductsTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

class ProductExportController extends Controller
{
    public function export()
    {
        return Excel::download(new ProductsExport, 'productos_' . date('Y-m-d_His') . '.xlsx');
    }

    public function template()
    {
        return Excel::download(new ProductsTemplateExport, 'plantilla_productos_jspos.xlsx');
    }
}
