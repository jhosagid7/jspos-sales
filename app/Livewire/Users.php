<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\Attributes\On;


class Users extends Component
{
    use WithPagination;

    public User $user;
    public $user_id, $editing, $search, $pagination = 5, $pwd, $confirm_pwd, $temppwd;
    public $tab = 1; // Tab navigation for sidebar form
    public  $role, $roleSelectedId,  $permissionId, $roles = [];
    public $commission_percent = 0, $freight_percent = 0, $exchange_diff_percent = 0, $current_batch = '1';
    public $sellerCommission1Threshold, $sellerCommission1Percentage, $sellerCommission2Threshold, $sellerCommission2Percentage;
    public $discountRules = []; // Array of discount rules for this user (seller)
    
    // Printer Auth
    public $isNetwork = false;
    public $printerHost, $printerShare;

    protected $rules =
    [
        'user.name' => "required|max:200|unique:users,name",
        'user.email' => 'required|email|max:75',
        'user.password' => 'nullable',
        'user.status' => 'required|in:Active,Locked',
        'user.profile' => 'required', // agregar esta regla
        'user.commission_percentage' => 'nullable|numeric|min:0|max:100',
        'commission_percent' => 'nullable|numeric|min:0|max:100',
        'freight_percent' => 'nullable|numeric|min:0|max:100',
        'exchange_diff_percent' => 'nullable|numeric|min:0|max:1000',
        'sellerCommission1Threshold' => 'nullable|numeric',
        'sellerCommission1Percentage' => 'nullable|numeric',
        'sellerCommission2Threshold' => 'nullable|numeric',
        'sellerCommission2Percentage' => 'nullable|numeric',
        'user.printer_name' => 'nullable|string|max:55',
        'user.printer_width' => 'nullable|in:80mm,58mm',
        'user.is_network' => 'boolean',
        'user.printer_user' => 'nullable|string',
        'user.printer_password' => 'nullable|string',
        
        // Seller Credit Config
        'user.seller_allow_credit' => 'nullable|boolean',
        'user.seller_credit_days' => 'nullable|integer|min:0',
        'user.seller_credit_limit' => 'nullable|numeric|min:0',
        'user.seller_usd_payment_discount' => 'nullable|numeric|min:0|max:100',
        'user.sales_view_mode' => 'nullable|in:grid,list',

        // Contact Fields
        'user.phone' => 'nullable|max:25',
        'user.taxpayer_id' => 'nullable|max:45',
        'user.address' => 'nullable|max:255',
        'user.color' => 'nullable|string|max:7',
    ];

    protected $messages = [
        'user.name.required' => 'Nombre requerido',
        'user.name.max' => 'Nombre debe tener máximo 200 caracteres',
        'user.name.unique' => 'El nombre ya existe',
        'user.email.required' => 'Email requerido',
        'user.email.email' => 'Email inválido',
        'user.email.max' => 'Email debe tener máximo 75 caracteres',
        'user.status.required' => 'Estatus requerido',
        'user.status.in' => 'Elige un estatus',
        'user.name.string' => 'Solo caracteres alfabeticos',
        'user.profile' => 'Selecciona un perfil', // agregar esta regla
        'user.commission_percentage.numeric' => 'El porcentaje debe ser numérico',
        'user.commission_percentage.min' => 'El porcentaje no puede ser negativo',
        'user.commission_percentage.max' => 'El porcentaje no puede ser mayor a 100',
    ];


    public function mount()
    {
        $this->user = new User();
        $this->user->status = 'Active';
        $this->user->profile = 0;
        $this->user->commission_percentage = 0;
        $this->commission_percent = 0;
        $this->freight_percent = 0;
        $this->exchange_diff_percent = 0;
        $this->exchange_diff_percent = 0;
        $this->current_batch = '1';
        $this->resetCommissionFields();
        $this->editing = false;

        session(['map' => 'Usuarios', 'child' => ' Componente ']);

        // Cargar los roles permitidos según módulos y permisos
        $this->loadAllowedRoles();

        if (count($this->roles) > 0) {
            $this->role = Role::find($this->roles[0]->id);
            $this->roleSelectedId = $this->role->id;
        }
    }

    public function render()
    {
        $users = $this->loadUsers();
        return view('livewire.users.users', [
            'users' => $users
        ]);
    }

    public function loadUsers()
    {
        $query = User::query();

        // Protect Super Admins from being visible to non-Super Admins
        if (!auth()->user()->hasRole('Super Admin')) {
            $query->whereDoesntHave('roles', function($q) {
                $q->where('name', 'Super Admin');
            });
        }

        if (strlen($this->search) > 0) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        return $query->orderBy('name', 'asc')->paginate($this->pagination);
    }

    public function Add()
    {
        $this->resetValidation();
        $this->resetExcept('user');
        $this->user->status = 'Active'; // Default to Active
        $this->user->profile = 0;
        $this->user->commission_percentage = 0;
        $this->commission_percent = 0;
        $this->freight_percent = 0;
        $this->exchange_diff_percent = 0;
        $this->resetCommissionFields();
        $this->editing = true;
        // Review: Reset passwords
        $this->pwd = '';
        $this->confirm_pwd = '';
        $this->tab = 1; // Reset to first tab
        
        // CRITICAL: Reload roles because resetExcept cleared them
        $this->loadAllowedRoles();
        
        // Reset discount rules
        $this->discountRules = [];

        $this->dispatch('init-new');
    }

    public function Edit(User $user)
    {
        // Protect Super Admin
        if ($user->hasRole('Super Admin') && !auth()->user()->hasRole('Super Admin')) {
            $this->dispatch('noty', msg: 'NO TIENES PERMISO PARA EDITAR AL SUPER ADMIN');
            return;
        }

        $this->user = $user;
        $this->editing = true;
        $this->tab = 1; // Reset to first tab
        $this->pwd = '********'; // Show dummy mask
        $this->temppwd = $user->password;
        $this->roleSelectedId = $this->getRoleId($user->profile);
        $this->role = Role::find($this->roleSelectedId);
        
        $this->sellerCommission1Threshold = $user->seller_commission_1_threshold;
        $this->sellerCommission1Percentage = $user->seller_commission_1_percentage;
        $this->sellerCommission2Threshold = $user->seller_commission_2_threshold;
        $this->sellerCommission2Percentage = $user->seller_commission_2_percentage;

        // Load Network Printer Settings
        $this->isNetwork = (bool) $user->is_network;
        $this->printerHost = null;
        $this->printerShare = null;

        if ($this->isNetwork && $user->printer_name) {
            $cleanName = str_replace('\\\\', '', $user->printer_name);
            $parts = explode('\\', $cleanName);
            if (count($parts) >= 2) {
                $this->printerHost = $parts[0];
                $this->printerShare = $parts[1];
            } else {
                 $this->printerHost = $cleanName;
            }
        }

        $latestConfig = $user->latestSellerConfig;
        if($latestConfig) {
            $this->commission_percent = $latestConfig->commission_percent;
            $this->freight_percent = $latestConfig->freight_percent;
            $this->exchange_diff_percent = $latestConfig->exchange_diff_percent;
            $this->current_batch = $latestConfig->current_batch;
        } else {
            $this->commission_percent = 0;
            $this->freight_percent = 0;
            $this->exchange_diff_percent = 0;
            $this->current_batch = '1';
        }

        // CRITICAL: Reload roles for the dropdown
        $this->loadAllowedRoles();

        // Load discount rules
        $this->loadDiscountRules();

        $this->dispatch('init-new');
    }

    public function cancelEdit()
    {
        $this->resetValidation();
        $this->resetExcept('user');
        $this->user = new User();
        $this->user->commission_percentage = 0;
        $this->commission_percent = 0;
        $this->freight_percent = 0;
        $this->exchange_diff_percent = 0;
        $this->isNetwork = false;
        $this->printerHost = null;
        $this->printerShare = null;
        $this->resetCommissionFields();
        $this->editing = false;
        $this->discountRules = [];
        $this->tab = 1;
    }

    public function Store()
    {
        // 1. Basic Validation
        $rules = [
            'user.name' => $this->user->id > 0 ? "required|max:200|unique:users,name,{$this->user->id}" : 'required|max:200|unique:users,name',
            'user.email' => $this->user->id > 0 ? "required|email|max:75|unique:users,email,{$this->user->id}" : 'required|email|max:75|unique:users,email',
            'user.phone' => 'nullable|max:25',
            'user.taxpayer_id' => 'nullable|max:45',
            'user.address' => 'nullable|max:255',
            'user.status' => 'required|in:Active,Locked',
            'user.profile' => 'required|not_in:0', 
            'user.color' => 'nullable|max:7',
        ];

        // Password validation: required for new users, optional for existing users
        // If editing and password is empty or is the dummy mask, don't validate
        if (!$this->user->id) {
            // New user: password required and must match confirmation
            $rules['pwd'] = 'required|same:confirm_pwd';
        } else {
            // Editing: only validate if user is changing password (not empty and not the mask)
            if (!empty($this->pwd) && $this->pwd !== '********') {
                $rules['pwd'] = 'same:confirm_pwd';
            }
        }

        $messages = [
            'user.name.required' => 'Nombre requerido',
            'user.name.unique' => 'El nombre ya existe',
            'user.email.required' => 'Email requerido',
            'user.email.unique' => 'El email ya existe',
            'user.profile.required' => 'Selecciona un perfil',
            'user.profile.not_in' => 'Selecciona un perfil válido',
            'pwd.required' => 'El password es requerido',
            'pwd.same' => 'Los passwords no coinciden',
        ];

        \Illuminate\Support\Facades\Log::info('Store: Start', ['user' => $this->user->toArray()]);

        try {
            $this->validate($rules, $messages);
            \Illuminate\Support\Facades\Log::info('Store: Validation Passed');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::error('Store: Validation Failed', ['errors' => $e->errors()]);
            // Dispatch notification for first error
            $firstError = collect($e->errors())->flatten()->first();
            $this->dispatch('noty', msg: 'ERROR: ' . $firstError);
            throw $e;
        }

        try {
            DB::beginTransaction(); // Start transaction

            // Password Logic
            if (!$this->user->id) { // New User
                $this->user->password = bcrypt($this->pwd);
            } else { // Edit
                // Only update if password is not empty AND not the dummy mask
                if (!empty($this->pwd) && $this->pwd !== '********') {
                    $this->user->password = bcrypt($this->pwd);
                } else {
                     // Ensure password is not overriden with null due to Livewire hydration of hidden fields
                     // We can reload it from DB or simply unset it if it was null, but let's be explicit:
                     if (!$this->user->password) {
                         $original = User::find($this->user->id);
                         $this->user->password = $original->password;
                     }
                }
            }

            // Defaults for new user if not set
            if(!$this->user->exists) {
                // $this->user->profile passed validation, so it has a value (e.g., 'Admin')
                // validation rules for commission_percentage handled?
                if($this->user->commission_percentage == null) $this->user->commission_percentage = 0;
                if($this->user->is_network == null) $this->user->is_network = 0;
                
                // Fix: Initialize seller fields for new user
                if($this->user->seller_allow_credit == null) $this->user->seller_allow_credit = 0;
            }
            
            \Illuminate\Support\Facades\Log::info('Store: Saving User...');
            $this->user->save();
            \Illuminate\Support\Facades\Log::info('Store: User Saved', ['id' => $this->user->id]);

            // Assign/Sync Role
            if ($this->user->profile) {
                $roleId = $this->getRoleId($this->user->profile);
                if ($roleId > 0) {
                     $this->assignRole($this->user->id, $roleId, false); // Silently assign
                }
            }

            // Sync Commission Fields to User Model
            $this->user->seller_commission_1_threshold = $this->sellerCommission1Threshold;
            $this->user->seller_commission_1_percentage = $this->sellerCommission1Percentage;
            $this->user->seller_commission_2_threshold = $this->sellerCommission2Threshold;
            $this->user->seller_commission_2_percentage = $this->sellerCommission2Percentage;

            $this->user->save(); // Save again to persistence commission changes

            // Create History Record (SellerConfig)
            // Use permission check instead of hardcoded role name
            if ($this->isSeller($this->user->profile)) {
                // Ensure values are not null
                $commPercent = $this->commission_percent ?? 0;
                $freightPercent = $this->freight_percent ?? 0;
                $diffPercent = $this->exchange_diff_percent ?? 0;
                $batch = $this->current_batch ?? '1';
                
                \App\Models\SellerConfig::create([
                    'user_id' => $this->user->id,
                    'commission_percent' => $commPercent,
                    'freight_percent' => $freightPercent,
                    'exchange_diff_percent' => $diffPercent,
                    'current_batch' => $batch
                ]);
                
                // Save discount rules within transaction (if seller)
                $this->saveDiscountRules();
            }

            DB::commit();
            
            $msg = $this->user->wasRecentlyCreated ? 'USUARIO CREADO CORRECTAMENTE' : 'DATOS ACTUALIZADOS';
            $this->dispatch('noty', msg: $msg);
            
            // Switch to Edit Mode triggers automatically in UI if we refresh user or set editing
            $this->editing = true;
            // No tab dispatch needed
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('User Store Error: ' . $e->getMessage());
            $this->dispatch('noty', msg: 'ERROR AL GUARDAR: ' . $e->getMessage());
        }
    }

    public function UpdateRole()
    {
        $this->validate([
            'user.profile' => 'required|not_in:0'
        ], ['user.profile.required' => 'Selecciona un perfil', 'user.profile.not_in' => 'Selecciona un perfil válido']);

         // Validate Role Assignment Permission
        $targetRoleName = $this->user->profile;
        $currentUserRole = auth()->user()->roles->first();
        $targetRole = Role::where('name', $targetRoleName)->first();

        // Extra check
        if (!$targetRole) {
             $this->dispatch('noty', msg: 'Perfil no válido');
             return;
        }

        $allowed = false;
        if (auth()->user()->email === 'jhosagid77@gmail.com') {
             $allowed = true;
        } elseif ($currentUserRole && $targetRole) {
            if ($currentUserRole->level >= 100) {
                $allowed = true; 
            } else {
                $allowed = $targetRole->level <= $currentUserRole->level;
            }
        }

        if (!$allowed) {
             $this->dispatch('noty', msg: 'NO TIENES PERMISO PARA ASIGNAR ESTE ROL');
             return;
        }

        $this->assignRole($this->user->id, $this->getRoleId($this->user->profile));
        // Force update of model
        $this->user->save();
        $this->dispatch('noty', msg: 'ROL ASIGNADO CORRECTAMENTE');
    }

    public function UpdatePrinter()
    {
        // Manual validation for network
        if ($this->isNetwork) {
             if (empty($this->printerHost) || empty($this->printerShare)) {
                 $this->dispatch('noty', msg: 'FALTAN DATOS DE IMPRESORA DE RED');
                 return;
             }
        }

        $this->user->is_network = $this->isNetwork;
        
        if ($this->isNetwork) {
            $host = trim($this->printerHost, '\\'); 
            $share = trim($this->printerShare, '\\');
            $this->user->printer_name = "\\\\{$host}\\{$share}";
            if($this->user->printer_user) $this->user->printer_user = trim($this->user->printer_user);
            if($this->user->printer_password) $this->user->printer_password = trim($this->user->printer_password);
        } else {
            $this->user->printer_user = null;
            $this->user->printer_password = null;
        }
        
        $this->user->save();
        $this->dispatch('noty', msg: 'CONFIGURACIÓN DE IMPRESORA GUARDADA');
    }

    public function UpdateCommissions()
    {
        $this->user->seller_commission_1_threshold = $this->sellerCommission1Threshold;
        $this->user->seller_commission_1_percentage = $this->sellerCommission1Percentage;
        $this->user->seller_commission_2_threshold = $this->sellerCommission2Threshold;
        $this->user->seller_commission_2_percentage = $this->sellerCommission2Percentage;
        
        $this->user->save();

        if($this->isSeller($this->user->profile)) {
            \App\Models\SellerConfig::create([
                'user_id' => $this->user->id,
                'commission_percent' => $this->commission_percent ?? 0,
                'freight_percent' => $this->freight_percent ?? 0,
                'exchange_diff_percent' => $this->exchange_diff_percent ?? 0,
                'current_batch' => $this->current_batch ?? '1'
            ]);
        }
        $this->dispatch('noty', msg: 'COMISIONES ACTUALIZADAS');
    }

    public function resetCommissionFields()
    {
        $this->sellerCommission1Threshold = null;
        $this->sellerCommission1Percentage = null;
        $this->sellerCommission2Threshold = null;
        $this->sellerCommission2Percentage = null;
    }

    public function getRoleId($roleName)
    {
        if ($roleName && $roleName !== '0' && $roleName !== 0) {
            $role = Role::where('name', $roleName)->first();
            return $role ? $role->id : 0;
        }
        return 0;
    }

    // Helper to check if a ROLE name has the Seller permission
    public function isSeller($roleName)
    {
        if(!$roleName) return false;
        
        // Caching optimization strictly within request if needed, 
        // but for now simple query is fine since it's not high frequency loop
        $role = Role::where('name', $roleName)->first();
        if($role) {
            return $role->hasPermissionTo('system.is_seller');
        }
        return false;
    }

    #[On('destroyUser')]
    public function Destroy($id)
    {
        $user = User::find($id);
        if ($user->sales->count() > 0) {
            $this->dispatch('noty', msg: 'EL USUARIO TIENE VENTAS RELACIONADAS, NO ES POSIBLE ELIMINARLO');
            return;
        }
        if ($user->purchases()->count() > 0) {
            $this->dispatch('noty', msg: 'EL USUARIO TIENE COMPRAS RELACIONADAS, NO ES POSIBLE ELIMINARLO');
            return;
        }

        // Protect Super Admin
        if ($user->hasRole('Super Admin') && !auth()->user()->hasRole('Super Admin')) {
            $this->dispatch('noty', msg: 'NO ES POSIBLE ELIMINAR A UN SUPER ADMIN');
            return;
        }

        $user->delete();

        $this->resetPage();


        $this->dispatch('noty', msg: 'USUARIO ELIMINADO CON ÉXITO');
    }

    public function assignRole($userId, $roleId, $notify = true)
    {
        // dd($this->editing);
        try {

            $user = User::find($userId);
            $role = Role::find($roleId);

            // Asigna el rol al usuario
            if ($roleId == 0) {
                $user->syncRoles([]); //eliminar roles
            } else {
                $user->syncRoles([$role]); //asignar role
            }

            if ($notify) {
                if ($roleId == 0) {
                    $this->dispatch('noty', msg: "Se eliminaron los roles al usuario $user->name");
                } else {
                    $this->dispatch('noty', msg: 'Se asignó el rol ' . $role->name . ' al usuario ' . $user->name);
                }
            }

            // $this->UpdateProfileRoleUser($userId, $role->name);

            //
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al intentar asignar el role: {$th->getMessage()} ");
        }
    }
    function updatedRoleSelectedId()
    {
        $this->role = Role::find($this->roleSelectedId);
    }

    public function UpdateProfileRoleUser($userId = null, $role)
    {
        try {
            DB::beginTransaction();

            $order = User::findOrFail($userId);
            $order->update([
                'profile' => $role,

            ]);

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            $this->dispatch('noty', msg: "Error al intentar actualizar el role $role del usuario \n {$th->getMessage()}");
        }
    }
    public $history = [];
    public $viewingUserId;

    public function viewHistory($userId)
    {
        $this->viewingUserId = $userId;
        $this->history = \App\Models\SellerConfig::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $this->dispatch('show-history-modal');
    }

    public function closeHistory()
    {
        $this->history = [];
        $this->dispatch('close-history-modal');
    }

    // Discount Rules Management
    public function addDiscountRule()
    {
        $this->discountRules[] = [
            'days_from' => 0,
            'days_to' => null,
            'discount_percentage' => 0,
            'rule_type' => 'early_payment', // Default type
            'description' => ''
        ];
    }

    public function removeDiscountRule($index)
    {
        unset($this->discountRules[$index]);
        $this->discountRules = array_values($this->discountRules); // Re-index array
    }

    public function loadDiscountRules()
    {
        if ($this->user->id) {
            $rules = \App\Models\CreditDiscountRule::where('entity_type', 'seller')
                ->where('entity_id', $this->user->id)
                ->orderBy('days_from')
                ->get();

            $this->discountRules = $rules->map(function($rule) {
                return [
                    'id' => $rule->id,
                    'days_from' => $rule->days_from,
                    'days_to' => $rule->days_to,
                    'discount_percentage' => $rule->discount_percentage,
                    'rule_type' => $rule->rule_type,
                    'description' => $rule->description
                ];
            })->toArray();
        } else {
            $this->discountRules = [];
        }
    }

    public function saveDiscountRules()
    {
        if (!$this->user->id) {
            return;
        }

        // Delete existing rules for this seller
        \App\Models\CreditDiscountRule::where('entity_type', 'seller')
            ->where('entity_id', $this->user->id)
            ->delete();

        // Save new rules
        foreach ($this->discountRules as $rule) {
            if (isset($rule['days_from']) && isset($rule['discount_percentage'])) {
                \App\Models\CreditDiscountRule::create([
                    'entity_type' => 'seller',
                    'entity_id' => $this->user->id,
                    'days_from' => $rule['days_from'],
                    'days_to' => $rule['days_to'],
                    'discount_percentage' => $rule['discount_percentage'],
                    'rule_type' => $rule['rule_type'],
                    'description' => $rule['description'] ?? ''
                ]);
            }
        }
    }
    public function loadAllowedRoles()
    {
        $modules = session('tenant.modules', []);
        $allRoles = \Spatie\Permission\Models\Role::orderBy('name')->get();

        $this->roles = $allRoles->filter(function ($role) use ($modules) {
            $name = strtolower($role->name);
            
            // Driver/Chofer requiere module_delivery
            if (in_array($name, ['driver', 'chofer', 'repartidor']) && !in_array('module_delivery', $modules)) {
                return false;
            }

            // Vendedor: Permitido siempre. Aunque el negocio sea Básico, 
            // siempre hay empleados que fungen como "Vendedor" en la caja.
            
            // Super Admin solo debe ser asignable por otro Super Admin
            if ($name === 'super admin' && !auth()->user()->hasRole('Super Admin')) {
                return false;
            }

            // Admin solo puede ser asignado por Admin o Super Admin
            if ($name === 'admin' && !auth()->user()->hasRole('Super Admin') && !auth()->user()->hasRole('Admin')) {
                return false;
            }

            return true;
        })->values();
    }
}
