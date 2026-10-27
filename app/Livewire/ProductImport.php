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
use App\Models\PriceList;
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
    public $warehouse_id = null;

    // System Fields available for mapping
    public $systemFields = [
        'name' => 'Nombre del Producto (Requerido)',
        'price' => 'Precio 1 / Precio de Venta (Principal)',
        'price_2' => 'Precio 2 (Opcional)',
        'price_3' => 'Precio 3 (Opcional)',
        'price_4' => 'Precio 4 (Opcional)',
        'cost' => 'Costo de Compra',
        'barcode' => 'Código de Barras / SKU',
        'stock_qty' => 'Stock Inicial / Existencia',
        'category' => 'Categoría (Crea si no existe)',
        'description' => 'Descripción',
    ];

    protected $rules = [
        'file' => 'required|file|mimes:xlsx,xls,csv,txt',
        'warehouse_id' => 'required|exists:warehouses,id',
    ];

    public function mount()
    {
        $config = Configuration::first();
        $this->warehouse_id = $config->default_warehouse_id ?? Warehouse::first()?->id;
    }

    public function updatedFile()
    {
        $this->validateOnly('file');
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
                $hNormalized = preg_replace('/[^a-z0-9]/', '', $hClean);
                $f = strtolower($field);

                $match = false;
                if ($h === $f || $hClean === $f || $hNormalized === $f) {
                    $match = true;
                }

                if (!$match) {
                    switch ($field) {
                        case 'name':
                            if (in_array($hClean, ['nombre', 'producto', 'articulo', 'item', 'nombre del producto', 'nombre producto', 'producto / descripcion'])) {
                                $match = true;
                            }
                            break;
                        case 'price':
                            if (in_array($hClean, [
                                'precio', 'venta', 'p.venta', 'pvp', 'precio_venta', 'precio venta', 
                                'precio de venta', 'p. venta', 'precio 1', 'precio1', 'pvp 1', 'pvp1', 
                                'precio de venta (principal)', 'precio principal', 'precio base'
                            ]) || in_array($hNormalized, ['precio', 'preciodeventa', 'precioventa', 'precio1', 'pvp', 'pvp1', 'precioprincipal', 'preciodeventaprincipal'])) {
                                $match = true;
                            }
                            break;
                        case 'price_2':
                            if (in_array($hClean, [
                                'precio 2', 'precio2', 'pvp 2', 'pvp2', 'precio_2', 'p.venta 2', 'precio mayor', 'mayor', 'mayorista'
                            ]) || in_array($hNormalized, ['precio2', 'pvp2', 'preciomayor', 'mayorista', 'preciomayorista'])) {
                                $match = true;
                            }
                            break;
                        case 'price_3':
                            if (in_array($hClean, [
                                'precio 3', 'precio3', 'pvp 3', 'pvp3', 'precio_3', 'p.venta 3', 'precio especial', 'especial'
                            ]) || in_array($hNormalized, ['precio3', 'pvp3', 'precioespecial'])) {
                                $match = true;
                            }
                            break;
                        case 'price_4':
                            if (in_array($hClean, [
                                'precio 4', 'precio4', 'pvp 4', 'pvp4', 'precio_4', 'p.venta 4'
                            ]) || in_array($hNormalized, ['precio4', 'pvp4'])) {
                                $match = true;
                            }
                            break;
                        case 'cost':
                            if (in_array($hClean, ['costo', 'compra', 'p.compra', 'precio_compra', 'costo compra', 'costo de compra', 'costo unitario']) || in_array($hNormalized, ['costo', 'costodecompra', 'costocompra', 'pcompra'])) {
                                $match = true;
                            }
                            break;
                        case 'barcode':
                            if (in_array($hClean, ['codigo', 'sku', 'barras', 'barcode', 'codigo_barras', 'codigo de barras', 'cod. barras', 'cod', 'cod barras', 'codigo de barras / sku']) || in_array($hNormalized, ['codigo', 'sku', 'codigodebarras', 'codbarras', 'barcode', 'codigodebarrassku'])) {
                                $match = true;
                            }
                            break;
                        case 'stock_qty':
                            if (in_array($hClean, ['stock', 'cantidad', 'existencia', 'existencias', 'qty', 'stock inicial', 'cant', 'stock_inicial']) || in_array($hNormalized, ['stock', 'cantidad', 'existencia', 'existencias', 'stockinicial'])) {
                                $match = true;
                            }
                            break;
                        case 'category':
                            if (in_array($hClean, ['categoria', 'rubro', 'familia', 'grupo', 'departamento']) || in_array($hNormalized, ['categoria', 'rubro', 'familia', 'grupo', 'departamento'])) {
                                $match = true;
                            }
                            break;
                        case 'description':
                            if (in_array($hClean, ['descripcion', 'detalle', 'observacion', 'observaciones', 'desc', 'descripcion del producto', 'especificaciones']) || in_array($hNormalized, ['descripcion', 'detalle', 'observaciones'])) {
                                $match = true;
                            }
                            break;
                    }
                }

                if ($match) {
                    if (!in_array((string)$colIdx, $this->mapping, true)) {
                        $this->mapping[$field] = (string)$colIdx;
                        break;
                    }
                }
            }
        }

        // Fallback: Si no se encontró columna "Nombre" pero existe una columna "Descripción" no mapeada
        if (!isset($this->mapping['name'])) {
            foreach ($this->headers as $colIdx => $header) {
                $hClean = strtolower(trim(@iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $header) ?: $header));
                if (in_array($hClean, ['descripcion', 'descripcion del producto']) && !in_array((string)$colIdx, $this->mapping, true)) {
                    $this->mapping['name'] = (string)$colIdx;
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

            $config = Configuration::first();
            $defaultWarehouseId = $config?->default_warehouse_id;
            
            if (!$defaultWarehouseId) {
                $firstWh = Warehouse::first();
                if (!$firstWh) {
                    $firstWh = Warehouse::create([
                        'name' => 'ALMACÉN PRINCIPAL',
                        'is_active' => true,
                        'is_partner_warehouse' => false,
                    ]);
                }
                $defaultWarehouseId = $firstWh->id;
            }

            if (empty($this->warehouse_id)) {
                $this->warehouse_id = $defaultWarehouseId;
            }

            $targetWarehouseId = (int)$this->warehouse_id;
            $isDefaultWarehouse = ($targetWarehouseId === (int)$defaultWarehouseId);

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

            $parseNumber = function($val) {
                if ($val === null || $val === '') return 0.0;
                if (is_numeric($val)) return (float)$val;

                $valStr = trim(strval($val));
                // Eliminar símbolos de moneda y caracteres no numéricos excepto signos y separadores
                $valStr = preg_replace('/[^\d.,\-+]/', '', $valStr);
                if ($valStr === '' || $valStr === '-') return 0.0;

                $hasComma = strpos($valStr, ',') !== false;
                $hasDot = strpos($valStr, '.') !== false;

                if ($hasComma && $hasDot) {
                    $lastComma = strrpos($valStr, ',');
                    $lastDot = strrpos($valStr, '.');
                    if ($lastComma > $lastDot) {
                        // Formato 1.500,50
                        $valStr = str_replace('.', '', $valStr);
                        $valStr = str_replace(',', '.', $valStr);
                    } else {
                        // Formato 1,500.50
                        $valStr = str_replace(',', '', $valStr);
                    }
                } else if ($hasComma) {
                    $valStr = str_replace(',', '.', $valStr);
                }

                return floatval($valStr);
            };

            // Detectar si el usuario mapeó alguna columna de precio adicional (Precio 2, Precio 3, etc.)
            $hasAdditionalPriceColumn = false;
            foreach (['price_2', 'price_3', 'price_4'] as $pKey) {
                if (isset($this->mapping[$pKey]) && $this->mapping[$pKey] !== '') {
                    $hasAdditionalPriceColumn = true;
                    break;
                }
            }

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

                $rawBarcode = !empty($productData['barcode']) ? trim(strval($productData['barcode'])) : null;
                $rawName = trim(strval($productData['name']));

                $price = $parseNumber($productData['price'] ?? 0);
                $cost = $parseNumber($productData['cost'] ?? 0);
                $stockQty = round($parseNumber($productData['stock_qty'] ?? 0), 2);

                // Recolectar precios adicionales (si vinieron y están mapeados)
                $rowAdditionalPrices = [];
                if ($hasAdditionalPriceColumn) {
                    foreach (['price_2', 'price_3', 'price_4'] as $pKey) {
                        if (isset($this->mapping[$pKey]) && $this->mapping[$pKey] !== '') {
                            if (isset($productData[$pKey]) && $productData[$pKey] !== '') {
                                $parsedP = $parseNumber($productData[$pKey]);
                                if ($parsedP > 0) {
                                    $rowAdditionalPrices[] = $parsedP;
                                }
                            }
                        }
                    }
                }

                try {
                    $existingProduct = null;

                    // 1. Buscar primero por código de barras / SKU si se especificó en el archivo (incluye papelera para restaurar si aplica)
                    if (!empty($rawBarcode)) {
                        $existingProduct = Product::withTrashed()->where('sku', $rawBarcode)->first();
                    }

                    // 2. Si no se encontró por código o no traía código, buscar por Nombre exacto (insensible a mayúsculas/minúsculas y espacios)
                    if (!$existingProduct && $rawName !== '') {
                        $existingProduct = Product::withTrashed()->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($rawName)])->first();
                    }

                    if ($existingProduct) {
                        if ($existingProduct->trashed()) {
                            $existingProduct->restore();
                        }

                        // Producto existente: actualizar datos y evitar crear registros duplicados
                        $updateData = [
                            'name' => $rawName,
                            'price' => $price > 0 ? $price : $existingProduct->price,
                            'cost' => $cost > 0 ? $cost : $existingProduct->cost,
                            'category_id' => $categoryId ?: $existingProduct->category_id,
                        ];

                        if (!empty($productData['description'])) {
                            $updateData['description'] = $productData['description'];
                        }

                        if (!empty($rawBarcode) && empty($existingProduct->sku)) {
                            $updateData['sku'] = $rawBarcode;
                        }

                        // Solo actualizar el stock_qty de tienda si se está importando al almacén principal
                        if ($isDefaultWarehouse && isset($productData['stock_qty']) && $productData['stock_qty'] !== '') {
                            $updateData['stock_qty'] = $stockQty;
                        }

                        $existingProduct->update($updateData);
                        $product = $existingProduct;

                        // Actualizar precios adicionales solo si la planilla contenía y mapeó columnas de precios adicionales
                        if ($hasAdditionalPriceColumn && !empty($rowAdditionalPrices)) {
                            PriceList::where('product_id', $product->id)->delete();
                            $priceRows = array_map(function($p) use ($product) {
                                return [
                                    'product_id' => $product->id,
                                    'price' => $p,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                            }, $rowAdditionalPrices);
                            PriceList::insert($priceRows);
                        }
                    } else {
                        // Producto nuevo: crear registro
                        $finalBarcode = !empty($rawBarcode) ? $rawBarcode : ('GEN-' . date('YmdHis') . '-' . $index);

                        $product = Product::create([
                            'name' => $rawName,
                            'sku' => $finalBarcode,
                            'description' => $productData['description'] ?? '',
                            'price' => $price,
                            'cost' => $cost,
                            'stock_qty' => $isDefaultWarehouse ? $stockQty : 0,
                            'category_id' => $categoryId,
                            'supplier_id' => $supplierId,
                            'type' => 'physical',
                            'status' => 'available',
                            'manage_stock' => 1,
                            'low_stock' => 10
                        ]);

                        // Guardar precios adicionales si fueron especificados
                        if (!empty($rowAdditionalPrices)) {
                            $priceRows = array_map(function($p) use ($product) {
                                return [
                                    'product_id' => $product->id,
                                    'price' => $p,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ];
                            }, $rowAdditionalPrices);
                            PriceList::insert($priceRows);
                        }
                    }

                    if ($targetWarehouseId && (isset($productData['stock_qty']) && $productData['stock_qty'] !== '')) {
                        ProductWarehouse::updateOrCreate(
                            ['product_id' => $product->id, 'warehouse_id' => $targetWarehouseId],
                            ['stock_qty' => $stockQty]
                        );
                    }

                    $this->successCount++;
                } catch (\Exception $e) {
                    $this->importErrors[] = "Fila #" . ($index + 1) . " ({$rawName}): " . $e->getMessage();
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
        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $config = Configuration::first();
        return view('livewire.product-import', compact('warehouses', 'config'));
    }
}
