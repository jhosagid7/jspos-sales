<?php

namespace App\Livewire\Workshop;

use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Services\WorkOrderBillingService;
use Livewire\Component;
use Livewire\WithPagination;

class WorkOrders extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public ?int $selectedOrderId = null;

    public array $newOrder = [
        'customer_id' => null,
        'vehicle_id' => null,
        'mechanic_id' => null,
        'entry_mileage' => null,
        'fuel_level' => '1/2',
        'initial_diagnosis' => '',
        'promised_at' => null,
    ];

    public array $newItem = [
        'type' => 'labor',
        'description' => '',
        'quantity' => 1,
        'unit_price' => 0.00,
        'unit_cost' => 0.00,
        'product_id' => null,
        'mechanic_id' => null,
        'mechanic_commission_percentage' => 0.00,
    ];

    public array $billingData = [
        'payment_method' => 'cash_usd',
        'exit_mileage' => null,
        'notes' => '',
    ];

    public array $quickCustomer = [
        'name' => '',
        'taxpayer_id' => '',
        'phone' => '',
        'address' => '',
    ];

    public array $quickVehicle = [
        'plate' => '',
        'brand' => '',
        'model' => '',
        'year' => '',
        'color' => '',
        'current_mileage' => '',
    ];

    public bool $showCreateModal = false;
    public bool $showItemModal = false;
    public bool $showBillingModal = false;
    public bool $showQuickCustomerModal = false;
    public bool $showQuickVehicleModal = false;

    // Smart Filter Properties for reception modal
    public string $customerSearch = '';
    public string $vehicleSearch = '';
    public string $mechanicSearch = '';

    // Smart Filter Properties for add item modal (parts)
    public string $productSearch = '';
    public string $productViewMode = 'list'; // 'list' or 'grid'

    protected function rules(): array
    {
        return [
            'newOrder.customer_id' => 'required|exists:customers,id',
            'newOrder.vehicle_id' => 'required|exists:vehicles,id',
            'newOrder.entry_mileage' => 'required|integer|min:0',
            'newOrder.initial_diagnosis' => 'required|string|min:3|max:2000',
            'newOrder.fuel_level' => 'nullable|string|max:20',
            'newOrder.mechanic_id' => 'nullable|exists:users,id',
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedNewOrderCustomerId($customerId): void
    {
        $this->newOrder['vehicle_id'] = null;
        $this->vehicleSearch = '';
    }

    public function selectCustomer(int $customerId): void
    {
        $this->newOrder['customer_id'] = $customerId;
        $this->newOrder['vehicle_id'] = null;
        $this->customerSearch = '';
        $this->vehicleSearch = '';
    }

    public function clearCustomer(): void
    {
        $this->newOrder['customer_id'] = null;
        $this->newOrder['vehicle_id'] = null;
        $this->customerSearch = '';
        $this->vehicleSearch = '';
    }

    public function selectVehicle(int $vehicleId): void
    {
        $this->newOrder['vehicle_id'] = $vehicleId;
        $this->vehicleSearch = '';

        $vehicle = Vehicle::find($vehicleId);
        if ($vehicle && !empty($vehicle->current_mileage) && empty($this->newOrder['entry_mileage'])) {
            $this->newOrder['entry_mileage'] = $vehicle->current_mileage;
        }
    }

    public function clearVehicle(): void
    {
        $this->newOrder['vehicle_id'] = null;
        $this->vehicleSearch = '';
    }

    public function selectMechanic(int $mechanicId): void
    {
        $this->newOrder['mechanic_id'] = $mechanicId;
        $this->mechanicSearch = '';
    }

    public function clearMechanic(): void
    {
        $this->newOrder['mechanic_id'] = null;
        $this->mechanicSearch = '';
    }

    public function selectOrder(int $orderId): void
    {
        $this->selectedOrderId = $orderId;
        $order = WorkOrder::find($orderId);
        if ($order && empty($this->billingData['exit_mileage'])) {
            $this->billingData['exit_mileage'] = $order->entry_mileage;
        }
    }

    public function storeOrder(): void
    {
        $this->validate();

        $userId = auth()->id() ?: User::first()?->id ?: 1;

        $order = WorkOrder::create([
            'customer_id' => $this->newOrder['customer_id'],
            'vehicle_id' => $this->newOrder['vehicle_id'],
            'user_id' => $userId,
            'mechanic_id' => $this->newOrder['mechanic_id'] ?: null,
            'entry_mileage' => (int)$this->newOrder['entry_mileage'],
            'fuel_level' => $this->newOrder['fuel_level'] ?: '1/2',
            'initial_diagnosis' => $this->newOrder['initial_diagnosis'],
            'status' => 'reception',
        ]);

        $this->newOrder = [
            'customer_id' => null,
            'vehicle_id' => null,
            'mechanic_id' => null,
            'entry_mileage' => null,
            'fuel_level' => '1/2',
            'initial_diagnosis' => '',
            'promised_at' => null,
        ];

        $this->showCreateModal = false;
        $this->selectedOrderId = $order->id;
        $this->dispatch('order-created', orderId: $order->id);
    }

    public function addItem(): void
    {
        if (!$this->selectedOrderId) {
            return;
        }

        $this->validate([
            'newItem.description' => 'required|string|min:2|max:255',
            'newItem.quantity' => 'required|numeric|min:0.01',
            'newItem.unit_price' => 'required|numeric|min:0',
            'newItem.type' => 'required|in:part,labor',
            'newItem.mechanic_id' => 'nullable|exists:users,id',
            'newItem.mechanic_commission_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $order = WorkOrder::findOrFail($this->selectedOrderId);
        $order->addItem($this->newItem);

        $this->newItem = [
            'type' => 'labor',
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0.00,
            'unit_cost' => 0.00,
            'product_id' => null,
            'mechanic_id' => null,
            'mechanic_commission_percentage' => 0.00,
        ];

        $this->showItemModal = false;
        $this->dispatch('item-added');
    }

    public function changeStatus(int $orderId, string $newStatus): void
    {
        $order = WorkOrder::findOrFail($orderId);
        $order->updateStatus($newStatus);
        $this->dispatch('status-updated', orderId: $orderId, status: $newStatus);
    }

    public function createWorkOrder(): void
    {
        $this->storeOrder();
    }

    public function saveQuickCustomer(): void
    {
        $this->validate([
            'quickCustomer.name' => 'required|string|min:3|max:255',
            'quickCustomer.taxpayer_id' => 'required|string|min:3|max:50|unique:customers,taxpayer_id',
            'quickCustomer.phone' => 'nullable|string|max:50',
            'quickCustomer.address' => 'nullable|string|max:255',
        ]);

        $customer = Customer::create([
            'name' => $this->quickCustomer['name'],
            'taxpayer_id' => $this->quickCustomer['taxpayer_id'],
            'phone' => $this->quickCustomer['phone'] ?? '',
            'address' => $this->quickCustomer['address'] ?? '',
        ]);

        $this->newOrder['customer_id'] = $customer->id;
        $this->newOrder['vehicle_id'] = null;

        $this->quickCustomer = [
            'name' => '',
            'taxpayer_id' => '',
            'phone' => '',
            'address' => '',
        ];

        $this->showQuickCustomerModal = false;
        $this->dispatch('customer-created', customerId: $customer->id);
    }

    public function saveQuickVehicle(): void
    {
        $this->validate([
            'newOrder.customer_id' => 'required|exists:customers,id',
            'quickVehicle.plate' => 'required|string|min:3|max:20|unique:vehicles,plate',
            'quickVehicle.brand' => 'required|string|min:2|max:100',
            'quickVehicle.model' => 'required|string|min:2|max:100',
            'quickVehicle.year' => 'nullable|integer|min:1950|max:' . (date('Y') + 1),
            'quickVehicle.color' => 'nullable|string|max:50',
            'quickVehicle.current_mileage' => 'nullable|integer|min:0',
        ]);

        $vehicle = Vehicle::create([
            'customer_id' => $this->newOrder['customer_id'],
            'plate' => $this->quickVehicle['plate'],
            'brand' => mb_strtoupper($this->quickVehicle['brand'], 'UTF-8'),
            'model' => mb_strtoupper($this->quickVehicle['model'], 'UTF-8'),
            'year' => !empty($this->quickVehicle['year']) ? (int)$this->quickVehicle['year'] : null,
            'color' => !empty($this->quickVehicle['color']) ? mb_strtoupper($this->quickVehicle['color'], 'UTF-8') : null,
            'current_mileage' => !empty($this->quickVehicle['current_mileage']) ? (int)$this->quickVehicle['current_mileage'] : 0,
        ]);

        $this->newOrder['vehicle_id'] = $vehicle->id;
        if (!empty($vehicle->current_mileage) && empty($this->newOrder['entry_mileage'])) {
            $this->newOrder['entry_mileage'] = $vehicle->current_mileage;
        }

        $this->quickVehicle = [
            'plate' => '',
            'brand' => '',
            'model' => '',
            'year' => '',
            'color' => '',
            'current_mileage' => '',
        ];

        $this->showQuickVehicleModal = false;
        $this->dispatch('vehicle-created', vehicleId: $vehicle->id);
    }

    public function billOrder(?int $orderId = null, ?string $paymentMethod = null, ?int $exitMileage = null): void
    {
        $targetId = $orderId ?: $this->selectedOrderId;
        if (!$targetId) {
            return;
        }

        $order = WorkOrder::findOrFail($targetId);

        $service = app(WorkOrderBillingService::class);
        $userId = auth()->id() ?: $order->user_id;

        $effectivePaymentMethod = $paymentMethod ?: ($this->billingData['payment_method'] ?? 'cash_usd');
        $effectiveExitMileage = !is_null($exitMileage)
            ? (int)$exitMileage
            : (!empty($this->billingData['exit_mileage']) ? (int)$this->billingData['exit_mileage'] : $order->entry_mileage);

        $service->bill($order, [
            'user_id' => $userId,
            'payment_method' => $effectivePaymentMethod,
            'exit_mileage' => $effectiveExitMileage,
            'notes' => $this->billingData['notes'] ?? '',
        ]);

        $this->showBillingModal = false;
        $this->dispatch('order-billed', orderId: $targetId);
    }

    public function openItemModal(): void
    {
        $this->productSearch = '';
        $this->newItem = [
            'type' => 'labor',
            'description' => '',
            'quantity' => 1,
            'unit_price' => 0.00,
            'unit_cost' => 0.00,
            'product_id' => null,
            'mechanic_id' => null,
            'mechanic_commission_percentage' => 0.00,
        ];

        // Default view mode from Configuration
        $config = \App\Models\Configuration::first();
        if ($config && !empty($config->sales_view_mode)) {
            $this->productViewMode = $config->sales_view_mode;
        }

        $this->showItemModal = true;
    }

    public function onProductSelected($productId): void
    {
        if (!$productId) {
            $this->newItem['product_id'] = null;
            return;
        }

        $product = Product::find($productId);
        if ($product) {
            $this->newItem['product_id'] = $product->id;
            $this->newItem['description'] = $product->name;
            $this->newItem['unit_cost'] = (float)$product->cost;

            // Calculate customer differential price if order has customer
            $price = (float)$product->price;
            if ($this->selectedOrderId) {
                $order = WorkOrder::with('customer.customerConfigs')->find($this->selectedOrderId);
                if ($order && $order->customer) {
                    $calcService = app(\App\Services\PriceCalculatorService::class);
                    $priceData = $calcService->calculate($product, null, $order->customer);
                    $price = (float)($priceData['final_price'] ?? $priceData['net_price'] ?? $product->price);
                }
            }

            $this->newItem['unit_price'] = round($price, 2);
        }
    }

    public function clearSelectedProduct(): void
    {
        $this->newItem['product_id'] = null;
        $this->newItem['description'] = '';
        $this->newItem['unit_price'] = 0.00;
        $this->newItem['unit_cost'] = 0.00;
        $this->productSearch = '';
    }

    public function render()
    {
        $query = WorkOrder::with(['customer', 'vehicle', 'mechanic', 'items'])
            ->search($this->search);

        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        $orders = $query->latest()->paginate(10);

        $selectedOrder = $this->selectedOrderId
            ? WorkOrder::with(['customer.customerConfigs', 'vehicle', 'mechanic', 'items.mechanic', 'items.product'])->find($this->selectedOrderId)
            : null;

        $customersQuery = Customer::query();
        if (!empty($this->customerSearch)) {
            $customersQuery->search($this->customerSearch);
        }
        $customers = $customersQuery->orderBy('name')->take(20)->get();

        // Ensure selected customer is present in list if already selected
        $selectedCustomerModel = !empty($this->newOrder['customer_id'])
            ? Customer::find($this->newOrder['customer_id'])
            : null;

        $vehiclesQuery = !empty($this->newOrder['customer_id'])
            ? Vehicle::where('customer_id', $this->newOrder['customer_id'])
            : Vehicle::query();

        if (!empty($this->vehicleSearch)) {
            $vehiclesQuery->search($this->vehicleSearch);
        }

        $vehicles = $vehiclesQuery->orderBy('plate')->take(20)->get();

        $selectedVehicleModel = !empty($this->newOrder['vehicle_id'])
            ? Vehicle::find($this->newOrder['vehicle_id'])
            : null;

        // Mechanics filtered strictly by role 'Mecanico'
        $mechanicsQuery = User::mechanics();
        if (!empty($this->mechanicSearch)) {
            $mechanicsQuery->search($this->mechanicSearch);
        }
        $mechanics = $mechanicsQuery->orderBy('name')->take(20)->get();

        $selectedMechanicModel = !empty($this->newOrder['mechanic_id'])
            ? User::find($this->newOrder['mechanic_id'])
            : null;

        // Products query with smart search
        $productsQuery = Product::where('status', 'available');
        if (!empty($this->productSearch)) {
            $productsQuery->search($this->productSearch);
        }
        $products = $productsQuery->orderBy('name')->take(24)->get();

        // If an item is already selected, load its model
        $selectedProductModel = !empty($this->newItem['product_id'])
            ? Product::find($this->newItem['product_id'])
            : null;

        $currencies = \App\Models\Currency::all();
        $sysConfig = \App\Models\Configuration::first();

        return view('livewire.workshop.work-orders', [
            'orders' => $orders,
            'selectedOrder' => $selectedOrder,
            'customers' => $customers,
            'selectedCustomerModel' => $selectedCustomerModel,
            'vehicles' => $vehicles,
            'selectedVehicleModel' => $selectedVehicleModel,
            'mechanics' => $mechanics,
            'selectedMechanicModel' => $selectedMechanicModel,
            'products' => $products,
            'selectedProductModel' => $selectedProductModel,
            'currencies' => $currencies,
            'sysConfig' => $sysConfig,
        ]);
    }
}
