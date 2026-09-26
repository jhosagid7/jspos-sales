<?php

namespace App\Http\Controllers;

use App\Exports\CustomersExport;
use App\Exports\CustomersTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

class CustomerExportController extends Controller
{
    public function export()
    {
        return Excel::download(new CustomersExport, 'clientes_' . date('Y-m-d_His') . '.xlsx');
    }

    public function template()
    {
        return Excel::download(new CustomersTemplateExport, 'plantilla_clientes_jspos.xlsx');
    }
}
