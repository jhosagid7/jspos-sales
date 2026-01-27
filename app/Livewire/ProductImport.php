<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;

class ProductImport extends Component
{
    use WithFileUploads;

    public $file;
    public $headers = [];
    public $mapping = [];
    public $preview = [];
    public $step = 1; // 1: Upload, 2: Map, 3: Preview/Import
    public $importing = false;
    public $importErrors = [];
    public $successCount = 0;

    // System Fields available for mapping
    public $systemFields = [
        'name' => 'Nombre (Requerido)',
        'description' => 'Descripción',
        'cost' => 'Costo (Comp)',
        'price' => 'Precio Venta',
        'barcode' => 'Código de Barras / SKU',
        'stock_qty' => 'Stock Inicial',
        'category' => 'Categoría (Crea si no existe)',
        //'brand' => 'Marca', // Optional based on user model
        //'min_stock' => 'Stock Mínimo'
    ];

    protected $rules = [
        'file' => 'required|file|mimes:xlsx,xls,csv',
    ];

    public function updatedFile()
    {
        $this->validate();
        $this->readHeaders();
    }

    public function readHeaders()
    {
        try {
            // Read ONLY the first row to get headers
            $data = Excel::toArray([], $this->file);
            
            if (empty($data) || empty($data[0])) {
                $this->addError('file', 'El archivo parece estar vacío o no es legible.');
                return;
            }

            $firstSheet = $data[0];
            if (empty($firstSheet[0])) {
                 $this->addError('file', 'No se encontraron cabeceras en la primera fila.');
                 return;
            }

            // USE VALUES, NOT KEYS. Ensure they are strings.
            $this->headers = array_map('strval', array_values($firstSheet[0]));
            
            $this->autoMap();
            $this->step = 2;

        } catch (\Exception $e) {
            $this->addError('file', 'Error al leer el archivo: ' . $e->getMessage());
        }
    }

    public function autoMap()
    {
        // Intelligent Mapping Logic
        foreach ($this->systemFields as $field => $label) {
            foreach ($this->headers as $header) {
                // Normalize strings for comparison
                $h = strtolower(trim($header));
                $f = strtolower($field);

                $match = false;
                
                // Direct match
                if ($h === $f) $match = true;
                
                // Common aliases (Spanish)
                if (!$match) {
                    switch ($field) {
                        case 'name':
                            if (in_array($h, ['nombre', 'producto', 'descripcion', 'item'])) $match = true;
                            break;
                        case 'price':
                            if (in_array($h, ['precio', 'venta', 'p.venta', 'pvp', 'precio_venta'])) $match = true;
                            break;
                        case 'cost':
                            if (in_array($h, ['costo', 'compra', 'p.compra', 'precio_compra'])) $match = true;
                            break;
                        case 'barcode':
                            if (in_array($h, ['codigo', 'sku', 'barras', 'barcode', 'codigo_barras'])) $match = true;
                            break;
                        case 'stock_qty':
                            if (in_array($h, ['stock', 'cantidad', 'existencia', 'qty'])) $match = true;
                            break;
                        case 'category':
                            if (in_array($h, ['categoria', 'rubro', 'familia', 'grupo'])) $match = true;
                            break;
                    }
                }

                if ($match) {
                    $this->mapping[$field] = $header;
                    break; // Stop looking for this field
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
             $data = Excel::toArray([], $this->file)[0]; // First sheet
             $headerRow = array_map('strval', array_values($data[0])); // Normalize headers for search

             DB::beginTransaction();
             
             // Get Default Supplier or Create One
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

             foreach ($data as $index => $row) {
                 if ($index === 0) continue; // Skip header row

                 // Resolve Data based on Mapping
                 $productData = [];
                 
                 // Required Name
                 $mappedHeader = $this->mapping['name'] ?? null;
                 if (!$mappedHeader) continue; // No mapping for required field
                 
                 $colIndex = array_search($mappedHeader, $headerRow);
                 if ($colIndex === false || !isset($row[$colIndex]) || empty($row[$colIndex])) {
                     continue; // Empty name in this row
                 }
                 $productData['name'] = $row[$colIndex];


                 // Map other fields
                 foreach ($this->mapping as $field => $headerObj) {
                     if ($field === 'name') continue; // Already handled
                     if ($headerObj) {
                         $cIndex = array_search($headerObj, $headerRow);
                         if ($cIndex !== false && isset($row[$cIndex])) {
                             $productData[$field] = $row[$cIndex];
                         } else {
                             $productData[$field] = null;
                         }
                     }
                 }

                 // Handle Category
                 $categoryId = 1; // Default
                 if (!empty($productData['category'])) {
                     $catName = trim($productData['category']);
                     $category = Category::where('name', 'like', $catName)->first();
                     if (!$category) {
                         $category = Category::create(['name' => $catName]);
                     }
                     $categoryId = $category->id;
                 } else {
                    // Get Default Category
                    $defCat = Category::first();
                    if ($defCat) $categoryId = $defCat->id;
                 }

                 // Handle Barcode (Generate if missing)
                 $barcode = $productData['barcode'] ?? null;
                 if (empty($barcode)) {
                     $barcode = 'GEN-' . time() . '-' . $index; // Simple generic
                 }

                 $exists = Product::where('sku', $barcode)->exists();
                 if ($exists) {
                     continue;
                 }

                 try {
                     Product::create([
                         'name' => $productData['name'],
                         'sku' => $barcode,
                         'description' => $productData['description'] ?? '',
                         'price' => floatval($productData['price'] ?? 0),
                         'cost' => floatval($productData['cost'] ?? 0),
                         'stock_qty' => intval($productData['stock_qty'] ?? 0),
                         'category_id' => $categoryId,
                         'supplier_id' => $supplierId, // Default Supplier
                         'type' => 'physical', // Enum default
                         'status' => 'available', // Enum default
                         'manage_stock' => 1, 
                         'low_stock' => 10
                     ]);
                     $this->successCount++;
                 } catch (\Exception $e) {
                     // Catch individual row errors
                     $this->importErrors[] = "Error en fila #$index ({$productData['name']}): " . $e->getMessage();
                 }
             }

             DB::commit();
             $this->step = 3; // Done
             $this->reset('file'); // Cleanup

        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('import', 'Error Crítico: ' . $e->getMessage());
            // Log for debugging
            \Illuminate\Support\Facades\Log::error('Import Error: ' . $e->getMessage());
        }
        
        $this->importing = false;
    }

    public function render()
    {
        return view('livewire.product-import');
    }
}
