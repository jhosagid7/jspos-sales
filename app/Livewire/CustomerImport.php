<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CustomerImport extends Component
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
        'phone' => 'Teléfono',
        'email' => 'Correo Electrónico',
        'address' => 'Dirección',
        'city' => 'Ciudad',
        'taxpayer_id' => 'RUT / DNI / ID',
        'type' => 'Tipo de Cliente',
        'seller' => 'Vendedor (Nombre)',
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
                            if (in_array($h, ['nombre', 'cliente', 'razon social', 'nombres'])) $match = true;
                            break;
                        case 'phone':
                            if (in_array($h, ['telefono', 'celular', 'movil', 'tel'])) $match = true;
                            break;
                        case 'email':
                            if (in_array($h, ['correo', 'mail', 'e-mail', 'correo electronico'])) $match = true;
                            break;
                        case 'address':
                            if (in_array($h, ['direccion', 'domicilio', 'ubicacion'])) $match = true;
                            break;
                        case 'city':
                            if (in_array($h, ['ciudad', 'municipio', 'poblacion'])) $match = true;
                            break;
                        case 'taxpayer_id':
                            if (in_array($h, ['rut', 'dni', 'c.i.', 'identificacion', 'cedula', 'nit'])) $match = true;
                            break;
                        case 'type':
                            if (in_array($h, ['tipo', 'clase', 'categoria'])) $match = true;
                            break;
                        case 'seller':
                            if (in_array($h, ['vendedor', 'asesor', 'ejecutivo'])) $match = true;
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
             
             foreach ($data as $index => $row) {
                 if ($index === 0) continue; // Skip header row

                 // Resolve Data based on Mapping
                 $customerData = [];
                 
                 // Required Name
                 $mappedHeader = $this->mapping['name'] ?? null;
                 if (!$mappedHeader) continue; // No mapping for required field
                 
                 $colIndex = array_search($mappedHeader, $headerRow);
                 if ($colIndex === false || !isset($row[$colIndex]) || empty($row[$colIndex])) {
                     continue; // Empty name in this row
                 }
                 $customerData['name'] = $row[$colIndex];


                 // Map other fields
                 foreach ($this->mapping as $field => $headerObj) {
                     if ($field === 'name') continue; // Already handled
                     if ($headerObj) {
                         $cIndex = array_search($headerObj, $headerRow);
                         if ($cIndex !== false && isset($row[$cIndex])) {
                             $customerData[$field] = $row[$cIndex];
                         } else {
                             $customerData[$field] = null;
                         }
                     }
                 }

                 // Check duplicates by taxpayer_id or email if present
                 if (!empty($customerData['taxpayer_id'])) {
                     if (Customer::where('taxpayer_id', $customerData['taxpayer_id'])->exists()) {
                         $this->importErrors[] = "Fila #$index: Cliente con NIT/RUT {$customerData['taxpayer_id']} ya existe.";
                         continue;
                     }
                 }
                 if (!empty($customerData['email'])) {
                     if (Customer::where('email', $customerData['email'])->exists()) {
                         $this->importErrors[] = "Fila #$index: Cliente con email {$customerData['email']} ya existe.";
                         continue;
                     }
                 }

                 // Handle Seller
                 $sellerId = Auth::id(); // Default to current user
                 
                 if (!empty($customerData['seller'])) {
                     $sellerName = trim($customerData['seller']);
                     
                     // Try to find existing seller
                     $seller = User::where('name', 'like', $sellerName)->first();
                     
                     if ($seller) {
                         $sellerId = $seller->id;
                     } else {
                         // Create new seller if requested
                         try {
                              $newSeller = User::create([
                                  'name' => $sellerName,
                                  'email' => strtolower(str_replace(' ', '.', $sellerName)) . '@example.com', // Placeholder email
                                  'password' => bcrypt('password'), // Default password
                                  'profile' => 'Vendedor',
                                  'status' => 'Active'
                              ]);
                              $newSeller->assignRole('Vendedor');
                              $sellerId = $newSeller->id;
                              
                              // Log this action for user info?
                              // $this->importErrors[] = "Info: Se creó el vendedor '$sellerName'";
                              
                         } catch (\Exception $e) {
                             // Fallback to auth user if creation fails (e.g. duplicate email gen)
                             $sellerId = Auth::id();
                         }
                     }
                 }

                 try {
                     Customer::create([
                         'name' => $customerData['name'],
                         'phone' => $customerData['phone'] ?? null,
                         'email' => $customerData['email'] ?? null,
                         'address' => $customerData['address'] ?? null,
                         'city' => $customerData['city'] ?? null,
                         'taxpayer_id' => $customerData['taxpayer_id'] ?? null,
                         'type' => $customerData['type'] ?? 'Minorista',
                         'seller_id' => $sellerId,
                     ]);
                     $this->successCount++;
                 } catch (\Exception $e) {
                     // Catch individual row errors
                     $this->importErrors[] = "Error en fila #$index ({$customerData['name']}): " . $e->getMessage();
                 }
             }

             DB::commit();
             $this->step = 3; // Done
             $this->reset('file'); // Cleanup

        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('import', 'Error Crítico: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Import Customer Error: ' . $e->getMessage());
        }
        
        $this->importing = false;
    }

    public function render()
    {
        return view('livewire.customer-import');
    }
}
