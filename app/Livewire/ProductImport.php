<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\ProductWarehouse;
use App\Models\Configuration;

class ProductImport extends Component
{
    use WithFileUploads;

    public $file;
    public $headers = []; // Array of [colIndex => HeaderTitle]
    public $mapping = []; // Array of [fieldKey => colIndexString]
    public $preview = [];
    public $step = 1; // 1: Upload, 2: Map, 3: Preview/Import
    public $importing = false;
    public $importErrors = [];
    public $successCount = 0;

    // System Fields available for mapping
    public $systemFields = [
        'name' => 'Nombre (Requerido)',
        'description' => 'Descripción',
        'cost' => 'Costo de Compra',
        'price' => 'Precio de Venta',
        'barcode' => 'Código de Barras / SKU',
        'stock_qty' => 'Stock Inicial / Existencia',
        'category' => 'Categoría (Crea si no existe)',
    ];

    protected $rules = [
        'file' => 'required|file|mimes:xlsx,xls,csv,txt',
    ];

    public function updatedFile()
    {
        $this->validate();
        $this->readHeaders();
    }

    public function readHeaders()
    {
        try {
            $path = $this->file->getRealPath();
            $data = Excel::toArray([], $path);
            
            if (empty($data) || empty($data[0])) {
                $this->addError('file', 'El archivo parece estar vacío o no es legible.');
                return;
            }

            $firstSheet = $data[0];
            if (empty($firstSheet[0])) {
                $this->addError('file', 'No se encontraron cabeceras en la primera fila.');
                return;
            }

            $headers = [];
            foreach ($firstSheet[0] as $colIdx => $colVal) {
                $val = trim(strval($colVal));
                if ($val !== '') {
                    $headers[$colIdx] = $val;
                }
            }

            $this->headers = $headers;
            $this->autoMap();
            $this->step = 2;

        } catch (\Exception $e) {
            $this->addError('file', 'Error al leer el archivo: ' . $e->getMessage());
        }
    }

    public function autoMap()
    {
        $this->mapping = [];
        foreach ($this->systemFields as $field => $label) {
            foreach ($this->headers as $colIdx => $header) {
                $h = strtolower(trim($header));
                $hClean = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $h);
                if (!$hClean) $hClean = $h;
                $f = strtolower($field);

                $match = false;
                if ($h === $f || $hClean === $f) $match = true;

                if (!$match) {
                    switch ($field) {
                        case 'name':
                            if (in_array($hClean, ['nombre', 'producto', 'descripcion', 'item', 'nombre del producto', 'descripcion del producto'])) $match = true;
                            break;
                        case 'price':
                            if (in_array($hClean, ['precio', 'venta', 'p.venta', 'pvp', 'precio_venta', 'precio venta', 'precio de venta', 'p. venta'])) $match = true;
                            break;
                        case 'cost':
                            if (in_array($hClean, ['costo', 'compra', 'p.compra', 'precio_compra', 'costo compra', 'costo de compra'])) $match = true;
                            break;
                        case 'barcode':
                            if (in_array($hClean, ['codigo', 'sku', 'barras', 'barcode', 'codigo_barras', 'codigo de barras', 'cod. barras', 'cod'])) $match = true;
                            break;
                        case 'stock_qty':
                            if (in_array($hClean, ['stock', 'cantidad', 'existencia', 'existencias', 'qty', 'stock inicial', 'cant'])) $match = true;
                            break;
                        case 'category':
                            if (in_array($hClean, ['categoria', 'rubro', 'familia', 'grupo', 'departamento'])) $match = true;
                            break;
                    }
                }

                if ($match) {
                    $this->mapping[$field] = (string)$colIdx;
                    break;
                }
            }
        }
    }

    public function import()
    {
        $this->importing = true;
        $this->importErrors = [];
        $this->successCount = 0;

        try {
            $path = $this->file->getRealPath();
            $data = Excel::toArray([], $path)[0];

            $mappedNameIdx = $this->mapping['name'] ?? null;
            if ($mappedNameIdx === null || $mappedNameIdx === '') {
                $this->addError('import', 'Debes seleccionar qué columna corresponde al Nombre del producto.');
                $this->importing = false;
                return;
            }

            $mappedNameIdx = (int)$mappedNameIdx;

            DB::beginTransaction();

            $defaultSupplier = Supplier::first();
            if (!$defaultSupplier) {
                $defaultSupplier = Supplier::create([
                    'name' => 'Proveedor General',
                    'address' => 'Local',
                    'phone' => '0000000',
                    'email' => 'general@example.com'
                ]);
            }
            $supplierId = $defaultSupplier->id;

            $config = Configuration::first();
            $defaultWarehouseId = $config->default_warehouse_id ?? Warehouse::first()?->id;

            $parseNumber = function($val) {
                if ($val === null || $val === '') return 0.0;
                $valStr = str_replace(' ', '', trim(strval($val)));
                if (strpos($valStr, ',') !== false && strpos($valStr, '.') !== false) {
                    $valStr = str_replace('.', '', $valStr);
                    $valStr = str_replace(',', '.', $valStr);
                } else if (strpos($valStr, ',') !== false) {
                    $valStr = str_replace(',', '.', $valStr);
                }
                return floatval($valStr);
            };

            foreach ($data as $index => $row) {
                if ($index === 0) continue; // Skip header row

                $rawName = isset($row[$mappedNameIdx]) ? trim(strval($row[$mappedNameIdx])) : '';
                if ($rawName === '') {
                    continue;
                }

                $productData = ['name' => $rawName];

                foreach ($this->mapping as $field => $colIdx) {
                    if ($field === 'name' || $colIdx === '' || $colIdx === null) continue;
                    $cIdx = (int)$colIdx;
                    if (isset($row[$cIdx])) {
                        $productData[$field] = trim(strval($row[$cIdx]));
                    } else {
                        $productData[$field] = null;
                    }
                }

                $categoryId = null;
                if (!empty($productData['category'])) {
                    $catName = trim($productData['category']);
                    $category = Category::where('name', 'like', $catName)->first();
                    if (!$category) {
                        $category = Category::create(['name' => $catName]);
                    }
                    $categoryId = $category->id;
                } else {
                    $defCat = Category::first();
                    if ($defCat) {
                        $categoryId = $defCat->id;
                    } else {
                        $defCat = Category::create(['name' => 'General']);
                        $categoryId = $defCat->id;
                    }
                }

                $barcode = $productData['barcode'] ?? null;
                if (empty($barcode)) {
                    $barcode = 'GEN-' . time() . '-' . $index;
                }

                $price = $parseNumber($productData['price'] ?? 0);
                $cost = $parseNumber($productData['cost'] ?? 0);
                $stockQty = (int)$parseNumber($productData['stock_qty'] ?? 0);

                try {
                    $existingProduct = Product::where('sku', $barcode)->first();
                    if ($existingProduct) {
                        $existingProduct->update([
                            'name' => $productData['name'],
                            'description' => $productData['description'] ?? $existingProduct->description,
                            'price' => $price > 0 ? $price : $existingProduct->price,
                            'cost' => $cost > 0 ? $cost : $existingProduct->cost,
                            'stock_qty' => $stockQty,
                            'category_id' => $categoryId
                        ]);
                        $product = $existingProduct;
                    } else {
                        $product = Product::create([
                            'name' => $productData['name'],
                            'sku' => $barcode,
                            'description' => $productData['description'] ?? '',
                            'price' => $price,
                            'cost' => $cost,
                            'stock_qty' => $stockQty,
                            'category_id' => $categoryId,
                            'supplier_id' => $supplierId,
                            'type' => 'physical',
                            'status' => 'available',
                            'manage_stock' => 1,
                            'low_stock' => 10
                        ]);
                    }

                    if ($defaultWarehouseId) {
                        ProductWarehouse::updateOrCreate(
                            ['product_id' => $product->id, 'warehouse_id' => $defaultWarehouseId],
                            ['stock_qty' => $stockQty]
                        );
                    }

                    $this->successCount++;
                } catch (\Exception $e) {
                    $this->importErrors[] = "Fila #" . ($index + 1) . " ({$productData['name']}): " . $e->getMessage();
                }
            }

            DB::commit();
            $this->step = 3;
            $this->reset('file');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('import', 'Error en la importación: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Import Error: ' . $e->getMessage());
        }

        $this->importing = false;
    }

    public function render()
    {
        return view('livewire.product-import');
    }
}
