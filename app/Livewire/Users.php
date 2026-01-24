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
    
    // Printer Auth
    public $isNetwork = false;
    public $printerHost, $printerShare;

    protected $rules =
    [
        'user.name' => "required|max:85|unique:users,name",
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
    ];

    protected $messages = [
        'user.name.required' => 'Nombre requerido',
        'user.name.max' => 'Nombre debe tener máximo 85 caracteres',
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

        // Simplemente cargar TODOS los roles disponibles
        $this->roles = Role::orderBy('name')->get();

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
        if (strlen($this->search) > 0)
            return User::where('name', 'like', "%{$this->search}%")->paginate($this->pagination);
        else
            return User::orderBy('name', 'asc')->paginate($this->pagination);
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
        $this->roles = Role::orderBy('name')->get();
        
        $this->dispatch('init-new');
    }

    public function Edit(User $user)
    {
        // Protect Super Admin
        if ($user->email === 'jhosagid77@gmail.com' && auth()->user()->email !== 'jhosagid77@gmail.com') {
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
        $this->roles = Role::orderBy('name')->get();

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
        $this->tab = 1;
    }

    public function Store()
    {
        // 1. Basic Validation
        $rules = [
            'user.name' => $this->user->id > 0 ? "required|max:85|unique:users,name,{$this->user->id}" : 'required|max:85|unique:users,name',
            'user.email' => $this->user->id > 0 ? "required|email|max:75|unique:users,email,{$this->user->id}" : 'required|email|max:75|unique:users,email',
            'user.status' => 'required|in:Active,Locked',
            'user.profile' => 'required|not_in:0', 
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
            if ($this->user->profile == 'Vendedor') {
                // Ensure values are not null
                $commPercent = $this->commission_percent ?? 0;
                $freightPercent = $this->freight_percent ?? 0;
                $diffPercent = $this->exchange_diff_percent ?? 0;
                $batch = $this->current_batch ?? '1';

                // Check if it's different from the last one to avoid spamming history? 
                // Or just save every time "Update" is clicked? User request implies they want history when they update.
                // We'll create it every time they save a Vendedor.
                
                \App\Models\SellerConfig::create([
                    'user_id' => $this->user->id,
                    'commission_percent' => $commPercent,
                    'freight_percent' => $freightPercent,
                    'exchange_diff_percent' => $diffPercent,
                    'current_batch' => $batch
                ]);
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

        if($this->user->profile == 'Vendedor') {
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
        if ($user->email === 'jhosagid77@gmail.com') {
            $this->dispatch('noty', msg: 'NO ES POSIBLE ELIMINAR AL SUPER ADMIN');
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
}
